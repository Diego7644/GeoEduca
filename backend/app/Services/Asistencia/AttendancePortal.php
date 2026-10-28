<?php

namespace App\Services\Asistencia;

use App\Models\AttendanceAttempt;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\InAppNotification;
use App\Models\Justification;
use App\Models\QrToken;
use App\Models\SchoolSetting;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Asistencia real. students.id no es users.id.
 * El GPS no marca asistencia: solo queda como intento.
 */
class AttendancePortal
{
    public function estudiante(User $user): array
    {
        $student = $this->ownStudent($user);
        $settings = $this->settings();
        $token = $this->currentOrIssueQr($student);
        $rows = $this->rows($student);
        $today = $this->todayRow($student);

        return [
            'profile' => [
                'name' => $student->name,
                'greeting' => $this->greeting(),
                'code' => $student->code,
                'grade' => $student->grade_long,
                'tutor' => $student->homeroomStaff?->name ?? 'Sin tutor asignado',
                'avatar' => null,
            ],
            'dateLabel' => $this->now()->format('Y-m-d'),
            'shift' => [
                'label' => 'Jornada',
                'detail' => 'Ingreso hasta '.$settings->entry_limit.' • Salida '.$settings->exit_time,
            ],
            'token' => $token,
            'gps' => [
                'campus' => $settings->school.' • '.$settings->campus,
                'gate' => 'Referencia institucional',
                'lat' => (float) $settings->lat,
                'lng' => (float) $settings->lng,
                'radius' => (int) $settings->geofence_m,
                'address' => $settings->address,
            ],
            'kpis' => $this->kpis($student, $settings),
            'today' => $today,
            'scheduleTitle' => 'Sin horario institucional cargado',
            'subjectCount' => 0,
            'exitLabel' => $settings->exit_time,
            'campus' => [
                'name' => $settings->school.' • '.$settings->campus,
                'address' => $settings->address,
            ],
            'classes' => [],
            'weekly' => [],
            'attendance' => $rows,
            'rfid' => [
                'title' => 'Carnet físico',
                'steps' => [
                    'No hay un lector NFC conectado a este servidor.',
                    'Si no puedes mostrar el QR, el auxiliar registra el ingreso con tu DNI.',
                ],
            ],
            'gpsNote' => 'La ubicación del navegador no confirma por sí sola que estés en el colegio.',
        ];
    }

    public function refreshQr(User $user): array
    {
        return ['token' => $this->issueQr($this->ownStudent($user), true)];
    }

    public function ubicacion(User $user, float $lat, float $lng, ?int $accuracy): array
    {
        $student = $this->ownStudent($user);
        $settings = $this->settings();
        $distance = $this->meters($lat, $lng, (float) $settings->lat, (float) $settings->lng);
        $uncertain = $accuracy === null || $accuracy > (int) $settings->geofence_m;
        $inside = ! $uncertain && $distance <= (int) $settings->geofence_m;
        $result = $uncertain ? 'uncertain' : ($inside ? 'inside' : 'outside');

        AttendanceAttempt::query()->create([
            'student_id' => $student->id,
            'actor_user_id' => $user->id,
            'kind' => AttendanceAttempt::KIND_GEO,
            'result' => $result,
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy_m' => $accuracy,
            'detail' => 'distancia '.round($distance).' m',
        ]);

        return [
            'result' => $result,
            'distance_m' => (int) round($distance),
            'radius_m' => (int) $settings->geofence_m,
            'marked_attendance' => false,
            'message' => $uncertain
                ? 'La precisión del GPS no alcanza para decidir si estás dentro del perímetro. No se registró asistencia.'
                : ($inside
                    ? 'Las coordenadas caen dentro del perímetro de referencia. Esto no marca la asistencia: hace falta el QR o el auxiliar.'
                    : 'Las coordenadas están fuera del perímetro de referencia. No se registró asistencia.'),
        ];
    }

    public function validarQr(User $actor, string $value): array
    {
        $this->assertAuxiliar($actor);
        $token = QrToken::query()->where('value', $value)->first();
        if (! $token) {
            $this->attempt(null, $actor, AttendanceAttempt::KIND_QR, 'invalid', 'código desconocido');
            throw new \RuntimeException('El código QR no es válido.');
        }

        $record = DB::transaction(function () use ($token, $actor) {
            $locked = QrToken::query()->whereKey($token->id)->lockForUpdate()->first();
            $student = Student::query()->find($locked->student_id);
            if ($locked->consumed_at !== null) {
                $this->attempt($student, $actor, AttendanceAttempt::KIND_QR, 'reused', 'código ya usado');
                throw new \RuntimeException('Ese código QR ya fue utilizado.');
            }
            if ($locked->expires_at->isPast()) {
                $this->attempt($student, $actor, AttendanceAttempt::KIND_QR, 'expired', 'código vencido');
                throw new \RuntimeException('Ese código QR ya expiró.');
            }
            $existing = AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->whereDate('attended_on', $this->now()->toDateString())
                ->exists();
            if ($existing) {
                throw new \RuntimeException('Ese estudiante ya tiene un registro de asistencia para hoy.');
            }
            $locked->forceFill(['consumed_at' => now()])->save();
            $created = $this->mark($student, $actor, 'qr', 'QR dinámico', 'Puerta');
            $this->attempt($student, $actor, AttendanceAttempt::KIND_QR, 'accepted', 'ingreso');

            return $created;
        });

        return [
            'message' => 'Ingreso registrado con QR.',
            'student' => ['id' => $record->student_id, 'name' => $record->student->name],
            'attendance' => $this->recordPayload($record),
            'panel' => $this->auxiliar($actor),
        ];
    }

    public function padre(User $user): array
    {
        $this->assertParent($user);

        return [
            'children' => $user->wards()->orderBy('name')->get()->map(fn (Student $student) => $this->child($student, $user))->values()->all(),
        ];
    }

    public function vincular(User $user, string $dni): array
    {
        $this->assertParent($user);
        $student = Student::query()->where('dni', $dni)->first();
        if (! $student) {
            throw new \RuntimeException('No hay un estudiante con ese DNI.');
        }
        if ($user->wards()->where('students.id', $student->id)->exists()) {
            throw new \RuntimeException('Ese estudiante ya está vinculado a su cuenta.');
        }
        throw new \RuntimeException('La vinculación la realiza administración. No puede reclamar un estudiante por su cuenta.');
    }

    public function justificar(User $user, array $data): array
    {
        $this->assertParent($user);
        $student = Student::query()->find($data['child_id']);
        if (! $student || ! $user->wards()->where('students.id', $student->id)->exists()) {
            throw new \RuntimeException('El estudiante no está vinculado a su cuenta.');
        }

        $item = Justification::query()->create([
            'student_id' => $student->id,
            'guardian_user_id' => $user->id,
            'type' => $data['type'],
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'] ?? null,
            'reason' => $data['reason'],
            'attachment_name' => $data['attachment_name'] ?? null,
            'status' => 'submitted',
        ]);
        $this->notifyStaff('Nueva justificación', $student->name.': '.$data['reason'], 'justification:'.$item->id);

        return ['message' => 'Solicitud enviada a Coordinación Académica.', 'panel' => $this->padre($user)];
    }

    public function auxiliar(User $user): array
    {
        $this->assertAuxiliar($user);
        $settings = $this->settings();
        $today = $this->now()->toDateString();
        $students = Student::query()->orderBy('name')->get();
        $entered = AttendanceRecord::query()->whereDate('attended_on', $today)->where('status', '!=', AttendanceRecord::STATUS_ABSENT)->count();
        $manual = AttendanceRecord::query()->whereDate('attended_on', $today)->where('source', 'manual')->count();
        $expected = max($students->count(), 1);

        return [
            'operator' => ['name' => $user->name, 'code' => 'USR-'.$user->id],
            'syncLot' => 'GT-'.$this->now()->format('Ymd'),
            'metrics' => [
                'entered' => $entered,
                'expected' => $students->count(),
                'remaining' => max(0, $students->count() - $entered),
                'percent' => round($entered / $expected * 100, 1),
                'manual' => $manual,
                'critical' => AttendanceAttempt::query()->whereDate('created_at', $today)->whereIn('result', ['invalid', 'expired', 'reused', 'outside'])->count(),
                'hardware' => [],
                'latency' => null,
            ],
            'cases' => [],
            'audit' => AttendanceRecord::query()->with('student')->latest('id')->limit(20)->get()->map(fn (AttendanceRecord $row) => [
                'id' => $row->id,
                'time' => optional($row->created_at)->timezone('America/Lima')->format('H:i:s'),
                'name' => $row->student->name,
                'grade' => $row->student->grade_short,
                'reason' => $row->reason ?: ($row->method ?: $row->source),
                'status' => $row->status,
                'operator' => $user->name,
                'notif' => ['', $row->exit_time ? 'Salida '.$row->exit_time : ''],
                'fresh' => null,
            ])->all(),
            'directory' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'grade' => $student->grade,
                'code' => $student->code,
                'dni' => $student->dni,
            ])->all(),
            'reader' => 'No hay lectores NFC ni molinetes conectados. El ingreso se registra con QR o con el DNI en este panel.',
        ];
    }

    public function aprobar(User $user, array $data): array
    {
        $this->assertAuxiliar($user);
        $student = $this->findStudent((string) $data['student_id']);
        if (! $student) {
            throw new \RuntimeException('No encontramos un estudiante con ese DNI o código.');
        }
        $reason = $data['reason'];
        if (! empty($data['notes'])) {
            $reason = mb_substr($reason.' — '.$data['notes'], 0, 255);
        }
        $this->mark($student, $user, 'manual', 'Ingreso en mesa', 'Mesa auxiliar', $reason);
        if (! empty($data['notify'])) {
            $this->notifyGuardians($student, 'Ingreso autorizado en puerta', $student->name.' • '.$reason, 'manual:'.$student->id.':'.$this->now()->toDateString());
        }

        return ['message' => 'Ingreso aprobado y anotado en la bitácora.', 'panel' => $this->auxiliar($user)];
    }

    public function salida(User $user, string $query): array
    {
        $this->assertPuedeRegistrar($user);
        $student = $this->findStudent($query);
        if (! $student) {
            throw new \RuntimeException('No encontramos un estudiante con ese DNI o código.');
        }
        $record = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attended_on', $this->now()->toDateString())
            ->first();
        if (! $record || $record->status === AttendanceRecord::STATUS_ABSENT) {
            throw new \RuntimeException('No hay una entrada de hoy para registrar la salida.');
        }
        if ($record->exit_time) {
            throw new \RuntimeException('La salida de hoy ya fue registrada.');
        }
        $record->forceFill(['exit_time' => $this->now()->format('H:i:s')])->save();
        $this->audit($user, $student->id, 'exit_recorded', 'Salida registrada.');

        return ['message' => 'Salida registrada.', 'panel' => $user->isAdmin() ? $this->reportes($user) : $this->auxiliar($user)];
    }

    public function cerrarJornada(User $user): array
    {
        $this->assertPuedeRegistrar($user);
        $settings = $this->settings();
        if ($this->minutes($this->now()->format('H:i')) < $this->minutes($settings->exit_time)) {
            throw new \RuntimeException('Aún no corresponde marcar ausencias: la jornada no ha terminado.');
        }
        if ((int) $this->now()->format('N') >= 6) {
            throw new \RuntimeException('Hoy no hay jornada escolar.');
        }
        $created = 0;
        $today = $this->now()->toDateString();
        foreach (Student::query()->orderBy('id')->get() as $student) {
            $exists = AttendanceRecord::query()->where('student_id', $student->id)->whereDate('attended_on', $today)->exists();
            if ($exists) {
                continue;
            }
            AttendanceRecord::query()->create([
                'student_id' => $student->id,
                'recorded_by_user_id' => $user->id,
                'attended_on' => $today,
                'status' => AttendanceRecord::STATUS_ABSENT,
                'source' => 'close',
                'method' => 'Cierre de jornada',
                'gate' => null,
                'reason' => 'Sin entrada al cierre de la jornada',
            ]);
            $created++;
        }
        $this->audit($user, null, 'day_closed', $created.' ausencia(s) generadas al cierre.');

        return ['message' => "Jornada cerrada. Ausencias nuevas: {$created}.", 'created' => $created];
    }

    public function justificaciones(User $user): array
    {
        $query = Justification::query()->with('student')->latest('id');
        if ($user->isParent()) {
            $query->where('guardian_user_id', $user->id);
        } elseif ($user->isStaff() && $user->staffProfile?->kind === StaffMember::KIND_DOCENTE) {
            $ids = Student::query()->where('homeroom_staff_id', $user->staffProfile->id)->pluck('id');
            $query->whereIn('student_id', $ids);
        } elseif (! $user->isAdmin() && $user->staffProfile?->kind !== StaffMember::KIND_AUXILIAR) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }

        return ['justifications' => $query->limit(50)->get()->map(fn (Justification $item) => $this->justificationPayload($item))->all()];
    }

    public function revisarJustificacion(User $user, int $id, string $decision): array
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new \RuntimeException('La decisión no es válida.');
        }
        $item = Justification::query()->with('student')->find($id);
        if (! $item) {
            throw new \RuntimeException('No encontramos la justificación.');
        }
        if (! $this->puedeRevisar($user, $item->student)) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }
        if ($item->status !== 'submitted') {
            throw new \RuntimeException('Esa justificación ya fue revisada.');
        }
        $item->forceFill([
            'status' => $decision,
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now(),
        ])->save();
        $this->audit($user, $item->student_id, 'justification_'.$decision, 'Revisión de justificación.');
        InAppNotification::query()->firstOrCreate(
            ['dedupe_key' => 'justification-review:'.$item->id],
            [
                'user_id' => $item->guardian_user_id,
                'role' => User::ROLE_PARENT,
                'title' => $decision === 'approved' ? 'Justificación aprobada' : 'Justificación rechazada',
                'body' => $item->student->name.' • '.$item->event_date->format('Y-m-d'),
            ]
        );

        return ['message' => $decision === 'approved' ? 'Justificación aprobada.' : 'Justificación rechazada.', 'justification' => $this->justificationPayload($item->fresh('student'))];
    }

    public function docente(User $user, ?string $date = null): array
    {
        $staff = $this->docenteOrFail($user);
        $day = $date ?: $this->now()->toDateString();
        $students = Student::query()->where('homeroom_staff_id', $staff->id)->orderBy('name')->get();

        return [
            'teacher' => ['name' => $user->name, 'kind' => StaffMember::KIND_DOCENTE],
            'date' => $day,
            'students' => $students->map(function (Student $student) use ($day) {
                $record = AttendanceRecord::query()->where('student_id', $student->id)->whereDate('attended_on', $day)->first();

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'dni' => $student->dni,
                    'grade' => $student->grade,
                    'today' => $record ? [
                        'statusLabel' => [
                            AttendanceRecord::STATUS_ON_TIME => 'A tiempo',
                            AttendanceRecord::STATUS_LATE => 'Tardanza',
                            AttendanceRecord::STATUS_ABSENT => 'Ausencia',
                        ][$record->status] ?? $record->status,
                        'time' => $record->entry_time,
                        'exit' => $record->exit_time,
                    ] : ['statusLabel' => 'Sin registro', 'time' => null, 'exit' => null],
                ];
            })->all(),
            'justifications' => $this->justificaciones($user)['justifications'],
        ];
    }

    public function reportes(User $user, array $filters = []): array
    {
        $rows = [];
        $query = AttendanceRecord::query()->with('student')->latest('attended_on');
        if (! empty($filters['from'])) {
            $query->whereDate('attended_on', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('attended_on', '<=', $filters['to']);
        }
        if (! empty($filters['student'])) {
            $needle = $filters['student'];
            $query->whereHas('student', function ($builder) use ($needle) {
                $builder->where('name', 'like', '%'.$needle.'%')->orWhere('dni', 'like', '%'.$needle.'%');
            });
        }
        if ($user->isStudent()) {
            $query->where('student_id', $this->ownStudent($user)->id);
        } elseif ($user->isParent()) {
            $query->whereIn('student_id', $user->wards()->pluck('students.id'));
        } elseif ($user->isStaff() && $user->staffProfile?->kind === StaffMember::KIND_DOCENTE) {
            $query->whereIn('student_id', Student::query()->where('homeroom_staff_id', $user->staffProfile->id)->pluck('id'));
        } elseif (! $user->isAdmin() && ! ($user->isStaff() && $user->staffProfile?->kind === StaffMember::KIND_AUXILIAR)) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }

        foreach ($query->limit(50)->get() as $record) {
            $rows[] = [
                $record->attended_on->format('Y-m-d'),
                $record->student->name,
                $record->status,
                $record->source,
                (string) ($record->entry_time ?? ''),
            ];
        }

        return [
            'title' => 'Asistencia registrada',
            'subtitle' => $user->name,
            'range_label' => trim(($filters['from'] ?? '').' '.($filters['to'] ?? '')) !== ''
                ? trim(($filters['from'] ?? '…').' a '.($filters['to'] ?? '…'))
                : 'Registros persistidos',
            'series_title' => 'No hay serie inventada',
            'series_unit' => 'count',
            'kpis' => [],
            'series' => [],
            'columns' => ['Fecha', 'Estudiante', 'Estado', 'Origen', 'Hora'],
            'rows' => $rows,
        ];
    }

    public function configuracion(User $user): array
    {
        return [
            'can_edit' => $this->canEditSettings($user),
            'config' => $this->settings()->only([
                'school', 'campus', 'address', 'year', 'entry_limit', 'exit_time',
                'geofence_m', 'promotion_min', 'tolerance_min', 'lat', 'lng',
            ]),
        ];
    }

    public function actualizarConfiguracion(User $user, array $input): array
    {
        if (! $this->canEditSettings($user)) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }
        if ($this->minutes($input['exit_time']) <= $this->minutes($input['entry_limit'])) {
            throw new \RuntimeException('La hora de salida debe ser posterior al límite de ingreso.');
        }
        $settings = $this->settings();
        $settings->fill($input)->save();
        $this->audit($user, null, 'settings_updated', 'Configuración institucional actualizada.');

        return ['message' => 'Configuración institucional actualizada.', ...$this->configuracion($user)];
    }

    public function notificaciones(User $user): array
    {
        $items = InAppNotification::query()->where('user_id', $user->id)->latest('id')->limit(30)->get();

        return ['notifications' => $items->map(fn (InAppNotification $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'body' => $item->body,
            'at' => optional($item->created_at)->timezone('America/Lima')->format('H:i'),
            'read' => $item->read_at !== null,
        ])->all()];
    }

    public function leerNotificaciones(User $user): array
    {
        InAppNotification::query()->where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        return $this->notificaciones($user);
    }

    public function directorioAdmin(): array
    {
        return [
            'students' => Student::query()->with('guardians:id,name,dni')->orderBy('name')->get()->map(fn (Student $student) => [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'dni' => $student->dni,
                'name' => $student->name,
                'code' => $student->code,
                'grade' => $student->grade,
                'guardians' => $student->guardians->map(fn (User $guardian) => [
                    'id' => $guardian->id,
                    'dni' => $guardian->dni,
                    'name' => $guardian->name,
                ])->all(),
            ])->all(),
            'staff' => StaffMember::query()->with('user:id,dni,role')->orderBy('name')->get()->map(fn (StaffMember $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'kind' => $member->kind,
                'user_id' => $member->user_id,
                'dni' => $member->user?->dni,
            ])->all(),
        ];
    }

    public function vincularAdmin(User $admin, string $guardianDni, string $studentDni): array
    {
        $guardian = User::query()->where('dni', $guardianDni)->where('role', User::ROLE_PARENT)->first();
        $student = Student::query()->where('dni', $studentDni)->first();
        if (! $guardian || ! $student) {
            throw new \RuntimeException('No encontramos al apoderado o al estudiante.');
        }
        $guardian->wards()->syncWithoutDetaching([$student->id]);
        $this->audit($admin, $student->id, 'admin_guardian_linked', 'Vinculación hecha por administración.');

        return ['message' => 'Apoderado vinculado.', 'directory' => $this->directorioAdmin()];
    }

    private function mark(Student $student, User $actor, string $source, string $method, string $gate, ?string $reason = null): AttendanceRecord
    {
        $existing = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attended_on', $this->now()->toDateString())
            ->first();
        if ($existing) {
            throw new \RuntimeException('Ese estudiante ya tiene un registro de asistencia para hoy.');
        }

        $settings = $this->settings();
        $now = $this->now();
        $lateMinutes = $this->minutes($now->format('H:i')) - $this->minutes($settings->entry_limit);
        $late = $lateMinutes > (int) $settings->tolerance_min;
        $record = AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'recorded_by_user_id' => $actor->id,
            'attended_on' => $now->toDateString(),
            'status' => $late ? AttendanceRecord::STATUS_LATE : AttendanceRecord::STATUS_ON_TIME,
            'source' => $source,
            'method' => $method,
            'gate' => $gate,
            'entry_time' => $now->format('H:i:s'),
            'late_minutes' => $late ? $lateMinutes : 0,
            'reason' => $reason,
        ]);
        $this->audit($actor, $student->id, 'attendance_recorded', $source);
        $this->notifyGuardians(
            $student,
            $late ? 'Tardanza registrada' : 'Asistencia registrada',
            $student->name.' • '.$now->format('H:i'),
            'attendance:'.$record->id
        );

        return $record->load('student');
    }

    private function issueQr(Student $student, bool $force): array
    {
        if (! $force) {
            $current = QrToken::query()
                ->where('student_id', $student->id)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();
            if ($current) {
                return $this->tokenPayload($current);
            }
        }

        $value = bin2hex(random_bytes(16));
        $token = QrToken::query()->create([
            'student_id' => $student->id,
            'value' => $value,
            'expires_at' => now()->addSeconds(60),
        ]);

        return $this->tokenPayload($token);
    }

    private function currentOrIssueQr(Student $student): array
    {
        return $this->issueQr($student, false);
    }

    private function tokenPayload(QrToken $token): array
    {
        return [
            'value' => $token->value,
            'hint' => substr($token->value, -4),
            'expires_in' => max(1, $token->expires_at->getTimestamp() - now()->getTimestamp()),
            'expires_at' => $token->expires_at->toIso8601String(),
        ];
    }

    private function ownStudent(User $user): Student
    {
        $student = Student::query()->where('user_id', $user->id)->first();
        if (! $student || ! $user->isStudent()) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }

        return $student;
    }

    private function assertParent(User $user): void
    {
        if (! $user->isParent()) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }
    }

    private function assertAuxiliar(User $user): void
    {
        if ($user->staffProfile?->kind !== StaffMember::KIND_AUXILIAR) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }
    }

    private function assertPuedeRegistrar(User $user): void
    {
        if ($user->isAdmin() || $user->staffProfile?->kind === StaffMember::KIND_AUXILIAR) {
            return;
        }
        throw new \RuntimeException('No tiene acceso a este panel.');
    }

    private function puedeRevisar(User $user, Student $student): bool
    {
        if ($user->isAdmin() || $user->staffProfile?->kind === StaffMember::KIND_AUXILIAR) {
            return true;
        }

        return $user->staffProfile?->kind === StaffMember::KIND_DOCENTE
            && (int) $student->homeroom_staff_id === (int) $user->staffProfile->id;
    }

    private function justificationPayload(Justification $item): array
    {
        return [
            'id' => $item->id,
            'student_id' => $item->student_id,
            'student' => $item->student->name,
            'type' => $item->type,
            'event_date' => $item->event_date->format('Y-m-d'),
            'reason' => $item->reason,
            'status' => $item->status,
            'reviewed_at' => $item->reviewed_at?->toIso8601String(),
        ];
    }

    private function docenteOrFail(User $user): StaffMember
    {
        $staff = $user->staffProfile;
        if (! $staff || $staff->kind !== StaffMember::KIND_DOCENTE) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }

        return $staff;
    }

    private function canEditSettings(User $user): bool
    {
        return $user->isAdmin() || $user->staffProfile?->kind === StaffMember::KIND_AUXILIAR;
    }

    private function findStudent(string $query): ?Student
    {
        $digits = preg_replace('/\D/', '', $query) ?? '';

        return Student::query()
            ->where('dni', $query)
            ->orWhere('code', $query)
            ->when($digits !== '', fn ($builder) => $builder->orWhere('dni', $digits))
            ->first();
    }

    private function child(Student $student, User $parent): array
    {
        $stats = $this->kpis($student, $this->settings());
        $alerts = Justification::query()->where('student_id', $student->id)->where('guardian_user_id', $parent->id)->latest('id')->limit(5)->get()
            ->map(fn (Justification $item) => [
                'id' => $item->id,
                'tone' => 'primary',
                'icon' => 'edit_document',
                'title' => 'Solicitud enviada',
                'time' => optional($item->created_at)->timezone('America/Lima')->format('H:i'),
                'text' => $item->reason,
                'meta' => $item->attachment_name ?: 'Sin archivo adjunto',
                'rowId' => null,
            ])->all();

        return [
            'id' => $student->id,
            'dni' => $student->dni,
            'name' => $student->name,
            'grade' => $student->grade_long.' • '.$student->shift,
            'short' => $student->grade_short,
            'code' => $student->code,
            'avatar' => null,
            'stats' => [
                'monthlyLabel' => $stats['punctuality'].'%',
                'latesLabel' => $stats['lates'].' este mes',
            ],
            'today' => [
                'dateLabel' => $this->now()->format('j M'),
                'badge' => $this->todayRow($student)['statusLabel'],
                'tone' => 'wait',
                'summary' => $this->todayRow($student)['statusLabel'],
                'rowId' => null,
            ],
            'attendance' => $this->rows($student),
            'alerts' => $alerts,
            'bus' => null,
            'compliance' => [
                'percent' => $stats['punctuality'],
                'minimum' => (int) $this->settings()->promotion_min,
                'exit' => $this->settings()->exit_time,
            ],
        ];
    }

    private function rows(Student $student): array
    {
        return AttendanceRecord::query()->where('student_id', $student->id)->orderByDesc('attended_on')->limit(40)->get()
            ->map(fn (AttendanceRecord $row) => [
                'id' => $row->id,
                'iso' => $row->attended_on->format('Y-m-d'),
                'date' => $row->attended_on->format('j M'),
                'status' => $row->status === AttendanceRecord::STATUS_ON_TIME ? 'puntual' : ($row->status === AttendanceRecord::STATUS_LATE ? 'tardanza' : 'falta'),
                'time' => $row->entry_time ? substr((string) $row->entry_time, 0, 5) : '—',
                'sub' => $row->source,
                'gate' => $row->gate,
                'gateIcon' => 'meeting_room',
                'method' => $row->method,
                'methodIcon' => $row->source === 'qr' ? 'qr_code_scanner' : 'badge',
                'exit' => $row->exit_time ? substr((string) $row->exit_time, 0, 5) : '—',
                'minutes' => $row->late_minutes,
                'justified' => Justification::query()->where('student_id', $student->id)->whereDate('event_date', $row->attended_on)->exists(),
                'justificationNote' => null,
            ])->all();
    }

    private function todayRow(Student $student): array
    {
        $record = AttendanceRecord::query()->where('student_id', $student->id)->whereDate('attended_on', $this->now()->toDateString())->first();
        if (! $record) {
            return [
                'status' => 'pendiente',
                'statusLabel' => 'Pendiente',
                'time' => null,
                'gate' => null,
                'method' => null,
                'notified' => false,
                'streak' => 0,
            ];
        }

        $label = [
            AttendanceRecord::STATUS_ON_TIME => 'A Tiempo',
            AttendanceRecord::STATUS_LATE => 'Tardanza',
            AttendanceRecord::STATUS_ABSENT => 'Sin registro',
        ][$record->status] ?? 'Registro';

        return [
            'status' => $record->status === AttendanceRecord::STATUS_ON_TIME ? 'puntual' : 'tardanza',
            'statusLabel' => $label,
            'time' => $record->entry_time ? substr((string) $record->entry_time, 0, 5) : null,
            'gate' => $record->gate,
            'method' => $record->method,
            'notified' => true,
            'streak' => 0,
        ];
    }

    private function kpis(Student $student, SchoolSetting $settings): array
    {
        $month = $this->now()->format('Y-m');
        $rows = AttendanceRecord::query()->where('student_id', $student->id)->where('attended_on', 'like', $month.'%')->get();
        $present = $rows->where('status', '!=', AttendanceRecord::STATUS_ABSENT)->count();
        $lates = $rows->where('status', AttendanceRecord::STATUS_LATE)->count();
        $onTime = $rows->where('status', AttendanceRecord::STATUS_ON_TIME)->count();
        $punctuality = $rows->isEmpty() ? 0 : round($onTime / $rows->count() * 100, 1);

        return [
            'punctuality' => $punctuality,
            'delta' => 0,
            'present' => $present,
            'expected' => $rows->count(),
            'unjustified' => $rows->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
            'lates' => $lates,
            'lateJustified' => false,
            'lateNote' => $lates === 0 ? 'Sin tardanzas registradas este mes' : $lates.' tardanza(s) registrada(s)',
            'promotion_min' => (int) $settings->promotion_min,
        ];
    }

    private function recordPayload(AttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'status' => $record->status,
            'source' => $record->source,
            'attended_on' => $record->attended_on->format('Y-m-d'),
        ];
    }

    private function notifyGuardians(Student $student, string $title, string $body, string $key): void
    {
        foreach ($student->guardians as $guardian) {
            InAppNotification::query()->firstOrCreate(
                ['dedupe_key' => $key.':'.$guardian->id],
                ['user_id' => $guardian->id, 'role' => $guardian->role, 'title' => $title, 'body' => $body]
            );
        }
    }

    private function notifyStaff(string $title, string $body, string $key): void
    {
        $staff = User::query()->where('role', User::ROLE_STAFF)->whereHas('staffProfile', fn ($query) => $query->where('kind', StaffMember::KIND_AUXILIAR))->get();
        foreach ($staff as $user) {
            InAppNotification::query()->firstOrCreate(
                ['dedupe_key' => $key.':'.$user->id],
                ['user_id' => $user->id, 'role' => $user->role, 'title' => $title, 'body' => $body]
            );
        }
    }

    private function attempt(?Student $student, User $actor, string $kind, string $result, string $detail): void
    {
        AttendanceAttempt::query()->create([
            'student_id' => $student?->id,
            'actor_user_id' => $actor->id,
            'kind' => $kind,
            'result' => $result,
            'detail' => $detail,
        ]);
    }

    private function audit(User $actor, ?int $studentId, string $action, ?string $reason): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor->id,
            'student_id' => $studentId,
            'action' => $action,
            'reason' => $reason,
            'metadata' => [],
        ]);
    }

    private function settings(): SchoolSetting
    {
        $existing = SchoolSetting::query()->first();
        if ($existing) {
            return $existing;
        }

        return SchoolSetting::query()->create([
            'school' => 'Colegio San Gabriel',
            'campus' => 'Sede Central',
            'address' => 'Av. Las Palmeras 450, Urb. Monterrico',
            'year' => '2026',
            'entry_limit' => '08:00',
            'exit_time' => '14:30',
            'geofence_m' => 50,
            'promotion_min' => 85,
            'tolerance_min' => 10,
            'lat' => -12.0862,
            'lng' => -77.0543,
        ]);
    }

    private function meters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    private function minutes(string $hm): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hm, 0, 5)));

        return $h * 60 + $m;
    }

    private function greeting(): string
    {
        $hour = (int) $this->now()->format('H');

        return $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    }

    private function now(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::now('America/Lima');
    }
}
