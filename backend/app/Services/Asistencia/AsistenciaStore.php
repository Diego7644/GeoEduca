<?php

namespace App\Services\Asistencia;

use App\Auth\DemoAccounts;
use App\Models\User;

/**
 * Estado de demostración (archivo JSON, sin BD).
 * La asistencia histórica se calcula al leer; aquí solo persisten
 * sesión, ajustes, justificaciones, vínculos y la bitácora del auxiliar.
 */
class AsistenciaStore
{
    private const AUTOMATIC_ENTRIES = 835;

    private const EXPECTED_ENTRIES = 920;

    private const WEEKDAYS = [
        1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves',
        5 => 'viernes', 6 => 'sábado', 7 => 'domingo',
    ];

    private const MONTHS = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    private const MONTHS_SHORT = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    private const REQUEST_TYPES = [
        'medical' => 'Motivo médico',
        'early_exit' => 'Salida anticipada',
        'tardy_excuse' => 'Tardanza excepcional',
        'absence' => 'Inasistencia programada',
    ];

    /**
     * Traduce un usuario persistido al actor del prototipo JSON (ids 1, 2, 3 y catálogo).
     * Las sesiones nuevas no se guardan en el JSON: el token vive en Sanctum.
     *
     * @return array{id: int, name: string, roleLabel: ?string, identifier: string, role: string}
     */
    public function actorForUser(User $user): array
    {
        foreach (DemoAccounts::all() as $account) {
            if ($account['dni'] === $user->dni && $account['role'] === $user->role) {
                return [
                    'id' => $account['id'],
                    'name' => $account['name'],
                    'roleLabel' => $account['roleLabel'],
                    'identifier' => $account['dni'],
                    'role' => $account['role'],
                ];
            }
        }

        if ($user->role === User::ROLE_STUDENT) {
            foreach ($this->catalog() as $student) {
                if ($student['dni'] === $user->dni) {
                    return [
                        'id' => $student['id'],
                        'name' => $user->name,
                        'roleLabel' => $user->role_label,
                        'identifier' => $user->dni,
                        'role' => $user->role,
                    ];
                }
            }
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'roleLabel' => $user->role_label,
            'identifier' => $user->dni,
            'role' => $user->role,
        ];
    }

    public function userForToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        return $this->state()['sessions'][$token] ?? null;
    }

    public function rememberSession(string $token, array $user): void
    {
        $this->mutate(function (array &$state) use ($token, $user) {
            $state['sessions'][$token] = $user;

            return true;
        });
    }

    public function forgetSession(string $token): void
    {
        if ($token === '') {
            return;
        }

        $this->mutate(function (array &$state) use ($token) {
            unset($state['sessions'][$token]);

            return true;
        });
    }

    public function passwordFor(int $id, string $fallback): string
    {
        return $this->state()['passwords'][(string) $id] ?? $fallback;
    }

    public function startRecovery(string $dni, string $role): array
    {
        $account = $this->account($dni, $role);
        if (! $account) {
            throw new \RuntimeException('No encontramos una cuenta con ese DNI para el perfil seleccionado.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->mutate(function (array &$state) use ($account, $code) {
            $state['recovery'][$account['role'].':'.$account['dni']] = [
                'code' => $code,
                'expires_at' => time() + 900,
                'id' => $account['id'],
            ];

            return true;
        });

        $payload = ['message' => 'Generamos un código de 6 dígitos, vigente por 15 minutos.'];
        if (app()->environment('local')) {
            $payload['demo_code'] = $code;
        }

        return $payload;
    }

    public function confirmRecovery(string $dni, string $role, string $code, string $password): array
    {
        $key = $role.':'.$dni;
        $this->mutate(function (array &$state) use ($key, $code, $password) {
            $entry = $state['recovery'][$key] ?? null;
            if (! $entry || (int) $entry['expires_at'] < time() || ! hash_equals((string) $entry['code'], $code)) {
                throw new \RuntimeException('El código no es válido o ya expiró.');
            }
            $state['passwords'][(string) $entry['id']] = $password;
            unset($state['recovery'][$key]);

            return true;
        });

        return ['message' => 'Contraseña actualizada. Ya puede iniciar sesión.'];
    }

    public function estudiante(array $actor): array
    {
        $this->guard($actor, 'student');
        $token = $this->issueToken((int) $actor['id'], false);
        $state = $this->state();
        $student = $this->requireStudent((int) $actor['id']);
        $config = $state['config'];
        $now = $this->now();
        $rows = $this->rowsFor($student, $state);
        $todayRow = $this->rowOn($rows, $now->format('Y-m-d'));
        $stats = $this->monthStats($rows, $now);
        $schedule = $this->daySchedule($now, $config);

        return [
            'profile' => [
                'name' => $student['name'],
                'greeting' => $this->greeting($now),
                'code' => $student['code'],
                'grade' => $student['gradeLong'],
                'tutor' => $student['tutor'],
                'avatar' => $student['avatar'],
            ],
            'dateLabel' => $this->longDate($now),
            'shift' => $this->shift($now, $config),
            'token' => $token,
            'gps' => [
                'campus' => $config['school'].' • '.$config['campus'],
                'gate' => 'Puerta 2 peatonal',
                'lat' => $config['lat'],
                'lng' => $config['lng'],
                'radius' => (int) $config['geofence_m'],
                'address' => $config['address'],
            ],
            'kpis' => $stats,
            'today' => $this->todayCard($student, $todayRow, $rows, $now, $config),
            'classes' => $schedule['classes'],
            'subjectCount' => $schedule['count'],
            'scheduleTitle' => 'Horario de Clases • '.ucfirst(self::WEEKDAYS[(int) $now->format('N')]).' '.$now->format('j'),
            'exitLabel' => $this->clock12($student['exit'] ?? $config['exit_time']),
            'weekly' => $this->weeklySchedule(),
            'campus' => [
                'name' => $config['school'].' • '.$config['campus'],
                'address' => $config['address'],
            ],
            'rfid' => [
                'title' => 'Carnet RFID de contingencia',
                'steps' => [
                    'Si el celular no tiene batería, presenta el carnet físico en la mesa del auxiliar.',
                    'El lector NFC de la Puerta Principal valida el chip sin abrir la app.',
                    'Si el carnet no lee, el auxiliar registra el ingreso con tu DNI y queda en la bitácora.',
                ],
            ],
        ];
    }

    public function refreshToken(array $actor): array
    {
        $this->guard($actor, 'student');

        return ['token' => $this->issueToken((int) $actor['id'], true)];
    }

    public function padre(array $actor): array
    {
        $this->guard($actor, 'parent');
        $state = $this->state();

        return [
            'phoneMasked' => '+51 987***210',
            'children' => array_map(
                fn (int $id) => $this->childBundle($this->requireStudent($id), $state),
                $this->linkedIds($state, (int) $actor['id'])
            ),
        ];
    }

    public function linkChild(array $actor, string $dni): array
    {
        $this->guard($actor, 'parent');
        $student = $this->findStudent($dni);
        if (! $student) {
            throw new \RuntimeException('No hay un estudiante con ese DNI.');
        }
        if ((int) ($student['guardianId'] ?? 0) !== (int) $actor['id']) {
            throw new \RuntimeException('Ese estudiante no está registrado a su cargo.');
        }

        $this->mutate(function (array &$state) use ($actor, $student) {
            $ids = $this->linkedIds($state, (int) $actor['id']);
            if (in_array($student['id'], $ids, true)) {
                throw new \RuntimeException('Ese estudiante ya está vinculado a su cuenta.');
            }
            $state['linked'][(string) $actor['id']][] = $student['id'];

            return true;
        });

        return $this->padre($actor);
    }

    public function justify(array $actor, array $data): array
    {
        $this->guard($actor, 'parent');
        $childId = (int) $data['child_id'];
        $state = $this->state();
        if (! in_array($childId, $this->linkedIds($state, (int) $actor['id']), true)) {
            throw new \RuntimeException('El estudiante no está vinculado a su cuenta.');
        }

        $student = $this->requireStudent($childId);
        $this->mutate(function (array &$state) use ($data, $student, $actor) {
            $state['justifications'][] = [
                'id' => 'j'.time().$student['id'],
                'child_id' => $student['id'],
                'parent_id' => (int) $actor['id'],
                'type' => $data['type'],
                'type_label' => self::REQUEST_TYPES[$data['type']] ?? $data['type'],
                'event_date' => $data['event_date'],
                'event_time' => $data['event_time'] ?? null,
                'reason' => $data['reason'],
                'attachment_name' => $data['attachment_name'] ?? null,
                'created_label' => $this->shortDate($this->now()).' '.$this->now()->format('H:i'),
            ];
            $state['notifications'][] = [
                'id' => 'nj'.bin2hex(random_bytes(4)),
                'role' => 'staff',
                'title' => 'Nueva justificación',
                'body' => $student['name'].' • '.(self::REQUEST_TYPES[$data['type']] ?? 'Solicitud'),
                'at' => $this->now()->format('H:i'),
            ];

            return true;
        });

        return $this->padre($actor);
    }

    public function auxiliar(array $actor): array
    {
        $this->guard($actor, 'staff');
        $state = $this->state();
        $cases = $this->openCases($state);
        $manual = (int) $state['manual_count'];
        $entered = self::AUTOMATIC_ENTRIES + $manual;

        return [
            'operator' => [
                'name' => $actor['name'],
                'code' => (int) $actor['id'] === 3 ? 'ID-4820' : 'ID-'.$actor['id'],
            ],
            'syncLot' => 'GT-'.$this->now()->format('Ymd'),
            'metrics' => [
                'entered' => $entered,
                'expected' => self::EXPECTED_ENTRIES,
                'remaining' => max(0, self::EXPECTED_ENTRIES - $entered),
                'percent' => round($entered / self::EXPECTED_ENTRIES * 100, 1),
                'manual' => $manual,
                'critical' => count($cases),
                'hardware' => [
                    ['label' => 'Molinete A (QR Óptico)', 'ok' => true],
                    ['label' => 'Molinete B (RFID/NFC)', 'ok' => true],
                ],
                'latency' => '42ms',
            ],
            'cases' => $cases,
            'audit' => $state['audit'],
            'directory' => array_values(array_map(fn (array $student) => [
                'id' => $student['id'],
                'name' => $student['name'],
                'grade' => $student['grade'],
                'code' => $student['code'],
                'dni' => $student['dni'],
            ], $this->catalog())),
        ];
    }

    public function approve(array $actor, array $data): array
    {
        $this->guard($actor, 'staff');
        $student = $this->findStudent($data['student_id']);
        if (! $student) {
            throw new \RuntimeException('No encontramos un estudiante con ese DNI o código.');
        }

        $this->mutate(function (array &$state) use ($actor, $data, $student) {
            foreach ($this->caseDefinitions() as $case) {
                if ($case['dni'] !== $student['dni'] && $case['code'] !== $student['code']) {
                    continue;
                }
                if (! in_array($case['id'], $state['resolved_cases'], true)) {
                    $state['resolved_cases'][] = $case['id'];
                }
            }
            if (! empty($data['case_id']) && ! in_array($data['case_id'], $state['resolved_cases'], true)) {
                $state['resolved_cases'][] = $data['case_id'];
            }
            $state['manual_count'] = (int) $state['manual_count'] + 1;
            $reason = $data['reason'];
            if (! empty($data['notes'])) {
                $reason = mb_substr($reason.' — '.$data['notes'], 0, 255);
            }
            array_unshift($state['audit'], [
                'id' => 'n'.bin2hex(random_bytes(4)),
                'time' => $this->now()->format('h:i:s A'),
                'name' => $student['name'],
                'grade' => $student['short'],
                'reason' => $reason,
                'dot' => 'bg-secondary',
                'notif' => ! empty($data['notify']) ? ['send', 'Push/SMS OK'] : ['notifications_off', 'Sin aviso'],
                'status' => 'Liberado',
                'fresh' => 'secondary',
                'operator' => $actor['name'],
            ]);
            $state['audit'] = array_slice($state['audit'], 0, 40);
            if (! empty($data['notify'])) {
                $state['notifications'][] = [
                    'id' => 'na'.bin2hex(random_bytes(4)),
                    'role' => 'parent',
                    'title' => 'Ingreso autorizado en puerta',
                    'body' => $student['name'].' • '.$data['reason'],
                    'at' => $this->now()->format('H:i'),
                ];
            }

            return true;
        });

        return $this->auxiliar($actor);
    }

    public function reportes(array $actor): array
    {
        $state = $this->state();
        $now = $this->now();

        return match ($actor['role'] ?? '') {
            'student' => $this->reportStudent($this->requireStudent((int) $actor['id']), $state, $now),
            'parent' => $this->reportParent($actor, $state, $now),
            'staff' => $this->reportStaff($state, $now),
            default => throw new \RuntimeException('No tiene acceso a este panel.'),
        };
    }

    public function configuracion(array $actor): array
    {
        return [
            'can_edit' => ($actor['role'] ?? '') === 'staff',
            'config' => $this->state()['config'],
        ];
    }

    public function updateConfig(array $actor, array $input): array
    {
        $this->guard($actor, 'staff');
        if ($this->toMinutes($input['exit_time']) <= $this->toMinutes($input['entry_limit'])) {
            throw new \RuntimeException('La hora de salida debe ser posterior al límite de ingreso.');
        }

        $config = $this->mutate(function (array &$state) use ($input) {
            foreach (['school', 'campus', 'address', 'year', 'entry_limit', 'exit_time'] as $key) {
                $state['config'][$key] = $input[$key];
            }
            foreach (['geofence_m', 'promotion_min', 'tolerance_min'] as $key) {
                $state['config'][$key] = (int) $input[$key];
            }

            return $state['config'];
        });

        return ['can_edit' => true, 'config' => $config];
    }

    public function notifications(array $actor): array
    {
        $state = $this->state();
        $read = $state['read_notifications'] ?? [];
        $items = array_merge($state['notifications'] ?? [], $this->baseNotifications($state, $actor));
        $out = [];
        foreach ($items as $item) {
            if (($item['role'] ?? '') !== $actor['role']) {
                continue;
            }
            $out[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'body' => $item['body'],
                'at' => $item['at'],
                'read' => in_array($item['id'], $read, true),
            ];
        }

        return $out;
    }

    public function markNotificationsRead(array $actor): array
    {
        $current = $this->notifications($actor);
        $this->mutate(function (array &$state) use ($current) {
            foreach ($current as $item) {
                if (! in_array($item['id'], $state['read_notifications'], true)) {
                    $state['read_notifications'][] = $item['id'];
                }
            }

            return true;
        });

        return $this->notifications($actor);
    }

    private function reportStudent(array $student, array $state, \DateTimeImmutable $now): array
    {
        $rows = $this->rowsFor($student, $state);
        $stats = $this->monthStats($rows, $now);
        [$series, $table] = $this->weekBuckets($rows);

        return [
            'title' => 'Reporte personal de asistencia',
            'subtitle' => $student['name'].' • '.$student['grade'],
            'range_label' => 'Año lectivo '.$state['config']['year'],
            'series_title' => 'Puntualidad por semana',
            'series_unit' => 'percent',
            'kpis' => [
                ['label' => 'Puntualidad del mes', 'value' => $stats['punctuality'].'%', 'hint' => $this->monthName($now)],
                ['label' => 'Asistencias', 'value' => $stats['present'].'/'.$stats['expected'], 'hint' => 'Días hábiles del mes'],
                ['label' => 'Tardanzas', 'value' => (string) $stats['lates'], 'hint' => $stats['lateNote']],
                ['label' => 'Faltas injustificadas', 'value' => (string) $stats['unjustified'], 'hint' => 'Meta de promoción '.$state['config']['promotion_min'].'%'],
            ],
            'series' => $series,
            'columns' => ['Semana', 'Puntualidad', 'Asistencias', 'Tardanzas'],
            'rows' => $table,
        ];
    }

    private function reportParent(array $actor, array $state, \DateTimeImmutable $now): array
    {
        $children = array_map(
            fn (int $id) => $this->childBundle($this->requireStudent($id), $state),
            $this->linkedIds($state, (int) $actor['id'])
        );
        $series = [];
        $table = [];
        foreach ($children as $child) {
            $series[] = [
                'label' => strtok($child['name'], ' '),
                'value' => $child['compliance']['percent'],
                'display' => $child['compliance']['percent'].'%',
            ];
            $table[] = [
                $child['name'],
                $child['stats']['monthlyLabel'],
                $child['stats']['latesLabel'],
                $child['today']['badge'],
            ];
        }

        return [
            'title' => 'Reporte familiar de asistencia',
            'subtitle' => $actor['name'].' • '.$state['config']['school'],
            'range_label' => $this->monthName($now).' '.$now->format('Y'),
            'series_title' => 'Puntualidad del mes por estudiante',
            'series_unit' => 'percent',
            'kpis' => [
                ['label' => 'Hijos vinculados', 'value' => (string) count($children), 'hint' => 'Puede vincular otro con su DNI'],
                ['label' => 'Meta de promoción', 'value' => $state['config']['promotion_min'].'%', 'hint' => 'Reglamento escolar'],
                ['label' => 'Tolerancia de ingreso', 'value' => $state['config']['tolerance_min'].' min', 'hint' => 'Después de las '.$this->clock12($state['config']['entry_limit'])],
            ],
            'series' => $series,
            'columns' => ['Estudiante', 'Puntualidad', 'Tardanzas', 'Hoy'],
            'rows' => $table,
        ];
    }

    private function reportStaff(array $state, \DateTimeImmutable $now): array
    {
        $cases = $this->openCases($state);
        $counts = [];
        foreach ($cases as $case) {
            $label = $case['badge']['label'];
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        foreach ($state['audit'] as $row) {
            $counts[$row['reason']] = ($counts[$row['reason']] ?? 0) + 1;
        }
        arsort($counts);
        $series = [];
        foreach (array_slice($counts, 0, 6, true) as $label => $value) {
            $series[] = ['label' => $label, 'value' => $value, 'display' => (string) $value];
        }
        $table = [];
        foreach (array_slice($state['audit'], 0, 8) as $row) {
            $table[] = [$row['time'], $row['name'], $row['grade'], $row['reason'], $row['status']];
        }
        $manual = (int) $state['manual_count'];
        $entered = self::AUTOMATIC_ENTRIES + $manual;

        return [
            'title' => 'Reporte operativo de puerta',
            'subtitle' => 'Lote '.$this->now()->format('Ymd').' • '.$state['config']['campus'],
            'range_label' => $this->longDate($now),
            'series_title' => 'Contingencias por motivo',
            'series_unit' => 'count',
            'kpis' => [
                ['label' => 'Ingresados', 'value' => $entered.'/'.self::EXPECTED_ENTRIES, 'hint' => round($entered / self::EXPECTED_ENTRIES * 100, 1).'% del aforo'],
                ['label' => 'Cola de revisión', 'value' => (string) count($cases), 'hint' => 'Casos presenciales abiertos'],
                ['label' => 'Ingresos manuales', 'value' => (string) $manual, 'hint' => 'Con bitácora del auxiliar'],
            ],
            'series' => $series,
            'columns' => ['Hora', 'Estudiante', 'Aula', 'Motivo', 'Estado'],
            'rows' => $table,
        ];
    }

    private function childBundle(array $student, array $state): array
    {
        $config = $state['config'];
        $now = $this->now();
        $rows = $this->rowsFor($student, $state);
        $todayRow = $this->rowOn($rows, $now->format('Y-m-d'));
        $stats = $this->monthStats($rows, $now);
        $word = $stats['punctuality'] >= 90 ? 'Puntual' : ($stats['punctuality'] >= (int) $config['promotion_min'] ? 'En observación' : 'Bajo la meta');

        return [
            'id' => $student['id'],
            'dni' => $student['dni'],
            'name' => $student['name'],
            'grade' => $student['gradeLong'].' • '.$student['shift'],
            'short' => $student['short'],
            'code' => $student['code'],
            'avatar' => $student['childAvatar'],
            'listAvatar' => $student['listAvatar'],
            'stats' => [
                'monthlyLabel' => $stats['punctuality'].'% ('.$word.')',
                'latesLabel' => $stats['lates'] === 0 ? 'Ninguna este mes' : $stats['lates'].' este mes',
            ],
            'today' => $this->parentToday($todayRow, $now, $student, $config),
            'attendance' => $rows,
            'alerts' => $this->alertsFor($student, $rows, $state),
            'bus' => $student['id'] === 1 ? [
                'name' => 'Ruta Escolar GEO #04',
                'status' => 'En destino',
                'detail' => 'Unidad Volvo B8R arribó a la sede central a las 07:38 AM con 28 estudiantes verificados a bordo.',
                'stop' => 'Puerta 2 Peatonal',
            ] : null,
            'compliance' => [
                'percent' => $stats['punctuality'],
                'minimum' => (int) $config['promotion_min'],
                'exit' => $this->clock12($student['exit'] ?? $config['exit_time']),
            ],
        ];
    }

    private function parentToday(?array $row, \DateTimeImmutable $now, array $student, array $config): array
    {
        $dateLabel = $this->shortDate($now);
        if ((int) $now->format('N') >= 6) {
            return ['dateLabel' => $dateLabel, 'badge' => 'SIN CLASES', 'tone' => 'wait', 'summary' => 'Hoy no hay jornada escolar.', 'rowId' => null];
        }
        if (! $row) {
            return ['dateLabel' => $dateLabel, 'badge' => 'PENDIENTE', 'tone' => 'wait', 'summary' => 'Aún no se registra el ingreso de hoy.', 'rowId' => null];
        }
        if ($row['status'] === 'falta') {
            return ['dateLabel' => $dateLabel, 'badge' => $row['justified'] ? 'FALTA JUSTIFICADA' : 'AUSENTE', 'tone' => 'miss', 'summary' => 'Sin marcación de ingreso en el perímetro.', 'rowId' => $row['id']];
        }
        if ($row['status'] === 'tardanza') {
            return [
                'dateLabel' => $dateLabel,
                'badge' => 'TARDANZA',
                'tone' => 'late',
                'summary' => 'Ingreso a las '.$row['time'].' por '.$row['gate'].' (+'.$row['minutes'].' min).',
                'rowId' => $row['id'],
            ];
        }

        return [
            'dateLabel' => $dateLabel,
            'badge' => 'PRESENTE',
            'tone' => 'ok',
            'summary' => 'Ingreso verificado '.$row['time'].' por '.$row['gate'],
            'rowId' => $row['id'],
        ];
    }

    private function alertsFor(array $student, array $rows, array $state): array
    {
        $alerts = [];
        foreach (array_reverse($state['justifications']) as $item) {
            if ((int) $item['child_id'] !== $student['id']) {
                continue;
            }
            $alerts[] = [
                'id' => $item['id'],
                'tone' => 'primary',
                'icon' => 'edit_document',
                'title' => 'Solicitud enviada',
                'time' => $item['created_label'],
                'text' => $item['type_label'].': '.$item['reason'],
                'meta' => $item['attachment_name'] ?: 'Sin archivo adjunto',
                'rowId' => null,
            ];
        }

        foreach (array_slice($rows, 0, 3) as $row) {
            if ($row['status'] === 'puntual') {
                $alerts[] = [
                    'id' => 'in-'.$row['id'],
                    'tone' => 'secondary',
                    'icon' => 'check_circle',
                    'title' => $row['today'] ? 'Ingreso confirmado' : 'Ingreso registrado',
                    'time' => $row['today'] ? 'Hoy '.$row['time'] : $row['date'].' '.$row['time'],
                    'text' => strtok($student['name'], ' ').' ingresó por '.$row['gate'].' a las '.$row['time'].' con '.$row['method'].'.',
                    'meta' => 'Perímetro escolar confirmado',
                    'rowId' => $row['id'],
                ];
            } elseif ($row['status'] === 'tardanza') {
                $alerts[] = [
                    'id' => 'late-'.$row['id'],
                    'tone' => 'tertiary',
                    'icon' => 'warning',
                    'title' => 'Alerta de tardanza',
                    'time' => $row['date'].' '.$row['time'],
                    'text' => 'Ingreso a las '.$row['time'].' (límite '.$this->clock12($state['config']['entry_limit']).').',
                    'meta' => $row['justified'] ? 'Tardanza justificada' : 'Pendiente de justificación',
                    'rowId' => $row['id'],
                ];
            } else {
                $alerts[] = [
                    'id' => 'miss-'.$row['id'],
                    'tone' => 'tertiary',
                    'icon' => 'event_busy',
                    'title' => 'Inasistencia',
                    'time' => $row['date'],
                    'text' => 'No hubo marcación de '.$student['name'].' en los puntos de acceso.',
                    'meta' => $row['justified'] ? 'Justificación recibida' : 'Sin justificación',
                    'rowId' => $row['id'],
                ];
            }
        }

        return array_slice($alerts, 0, 5);
    }

    private function todayCard(array $student, ?array $row, array $rows, \DateTimeImmutable $now, array $config): array
    {
        $streak = $this->streak($rows);
        $base = [
            'streak' => $streak,
            'rank' => ($row['status'] ?? '') === 'puntual' && $streak >= 5 ? 'Top 5% del Aula' : null,
            'notified' => (bool) $row,
        ];
        if ((int) $now->format('N') >= 6) {
            return $base + ['status' => 'weekend', 'statusLabel' => 'Sin clases', 'time' => null, 'gate' => null, 'method' => null];
        }
        if (! $row) {
            return $base + ['status' => 'pendiente', 'statusLabel' => 'Pendiente', 'time' => null, 'gate' => null, 'method' => null, 'notified' => false];
        }

        return $base + [
            'status' => $row['status'],
            'statusLabel' => ['puntual' => 'A Tiempo', 'tardanza' => 'Tardanza', 'falta' => 'Sin registro'][$row['status']] ?? 'Registro',
            'time' => $row['time'],
            'gate' => $row['gate'],
            'method' => $row['method'],
            'minutes' => $row['minutes'],
        ];
    }

    private function streak(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if ($row['status'] === 'puntual') {
                $count++;
                continue;
            }
            break;
        }

        return $count;
    }

    private function rowsFor(array $student, array $state): array
    {
        $config = $state['config'];
        $now = $this->now();
        $today = $now->format('Y-m-d');
        $yesterday = $now->modify('-1 day')->format('Y-m-d');
        $nowMin = $this->toMinutes($now->format('H:i'));
        $rows = [];

        foreach ($this->schoolDays($now->modify('-100 days'), $now) as $index => $date) {
            $iso = $date->format('Y-m-d');
            $plan = $this->plan($student['id'], $index, $date, $iso === $today);
            if ($plan['kind'] === 'present' && $iso === $today && $nowMin < $this->toMinutes($plan['entry'])) {
                continue;
            }

            $status = 'falta';
            $minutes = null;
            $time = '—';
            if ($plan['kind'] === 'present') {
                $entryMin = $this->toMinutes($plan['entry']);
                $deadline = $this->toMinutes($config['entry_limit']) + (int) $config['tolerance_min'];
                $lateBy = $entryMin - $this->toMinutes($config['entry_limit']);
                if ($entryMin <= $deadline) {
                    $status = 'puntual';
                } else {
                    $status = 'tardanza';
                    $minutes = max(1, $lateBy);
                }
                $time = $this->clock12($plan['entry']);
            }

            $justified = $status === 'tardanza' && ! empty($plan['justified']);
            $note = $justified ? 'Justificación médica ya aceptada' : null;
            $extra = $this->justificationFor($state, $student['id'], $iso);
            if ($extra && in_array($status, ['tardanza', 'falta'], true)) {
                $justified = true;
                $note = $extra['reason'];
                if (! empty($extra['attachment_name'])) {
                    $note .= ' • '.$extra['attachment_name'];
                }
            }

            $exitHm = $student['exit'] ?? $config['exit_time'];
            $exitMarked = $iso < $today || ($iso === $today && $nowMin >= $this->toMinutes($exitHm));
            $week = ucfirst(self::WEEKDAYS[(int) $date->format('N')]);
            $sub = $iso === $today ? 'Hoy • '.$week : ($iso === $yesterday ? 'Ayer • '.$week : $week);
            $gpsLat = number_format(-12.0862 + (($index % 5) - 2) * 0.0001, 4, '.', '');
            $gpsLng = number_format(-77.0543 + (($index % 3) - 1) * 0.0001, 4, '.', '');

            $rows[] = [
                'id' => $student['id'].'-'.$date->format('Ymd'),
                'iso' => $iso,
                'date' => $this->shortDate($date),
                'sub' => $sub,
                'time' => $time,
                'gate' => $plan['gate'],
                'gateIcon' => $plan['gateIcon'],
                'method' => $status === 'falta' ? '—' : $plan['method'],
                'methodIcon' => $status === 'falta' ? 'event_busy' : $plan['methodIcon'],
                'assisted' => $status !== 'falta' && ! empty($plan['assisted']),
                'exit' => $status === 'falta' ? '—' : ($this->clock12($exitHm).($exitMarked ? ' (Marcada)' : '')),
                'status' => $status,
                'today' => $iso === $today,
                'minutes' => $minutes,
                'justified' => $justified,
                'justificationNote' => $note,
                'voucher' => [
                    strtoupper($date->format('d').'-'.self::MONTHS_SHORT[(int) $date->format('n')].'-'.$date->format('Y')),
                    $time,
                    $plan['gate'],
                    $status === 'falta' ? 'Sin marcación' : $plan['method'],
                    $gpsLat.', '.$gpsLng,
                ],
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($b['iso'], $a['iso']));

        return $rows;
    }

    private function plan(int $studentId, int $index, \DateTimeImmutable $date, bool $isToday): array
    {
        $gates = [
            ['Puerta Peatonal 2', 'door_sliding'],
            ['Puerta Principal 1', 'meeting_room'],
        ];
        [$gate, $gateIcon] = $gates[$index % 2];
        $dow = (int) $date->format('N');

        if ($studentId === 1 && $isToday) {
            return $this->presentPlan('07:44', 'QR Dinámico', 'qr_code_scanner', $gate, $gateIcon, false, false);
        }
        if ($studentId === 1 && (int) $date->format('j') === 14) {
            return $this->presentPlan('08:12', 'Ingreso Asistido', 'face', 'Puerta Principal 1', 'meeting_room', true, true);
        }
        if ($studentId === 4 && $dow === 3) {
            return ['kind' => 'absent', 'gate' => $gate, 'gateIcon' => $gateIcon];
        }
        if ($studentId === 4 && $dow === 5) {
            return $this->presentPlan('08:18', 'Ingreso Asistido', 'face', $gate, $gateIcon, true, false);
        }
        if ($studentId === 5 && $dow === 1) {
            return $this->presentPlan('08:09', 'Carnet NFC', 'contactless', $gate, $gateIcon, false, true);
        }

        $entry = sprintf('07:%02d', 40 + ($index % 8));
        $method = $index % 4 === 0
            ? ['Carnet NFC', 'contactless']
            : ['QR Dinámico', 'qr_code_scanner'];

        return $this->presentPlan($entry, $method[0], $method[1], $gate, $gateIcon, false, false);
    }

    private function presentPlan(string $entry, string $method, string $icon, string $gate, string $gateIcon, bool $assisted, bool $justified): array
    {
        return [
            'kind' => 'present',
            'entry' => $entry,
            'method' => $method,
            'methodIcon' => $icon,
            'gate' => $gate,
            'gateIcon' => $gateIcon,
            'assisted' => $assisted,
            'justified' => $justified,
        ];
    }

    private function monthStats(array $rows, \DateTimeImmutable $now): array
    {
        $month = $now->format('Y-m');
        $previous = $now->modify('first day of last month')->format('Y-m');
        $current = array_values(array_filter($rows, fn ($row) => str_starts_with($row['iso'], $month)));
        $prior = array_values(array_filter($rows, fn ($row) => str_starts_with($row['iso'], $previous)));
        $pct = $this->punctuality($current);
        $delta = round($pct - $this->punctuality($prior), 1);
        $lates = array_values(array_filter($current, fn ($row) => $row['status'] === 'tardanza'));
        $lastLate = $lates[0] ?? null;
        $from = $now->modify('first day of this month');
        $expected = count($this->schoolDays($from, $now));
        $present = count(array_filter($current, fn ($row) => in_array($row['status'], ['puntual', 'tardanza'], true)));
        $unjustified = count(array_filter($current, fn ($row) => $row['status'] === 'falta' && ! $row['justified']));

        return [
            'punctuality' => $pct,
            'delta' => $delta,
            'present' => $present,
            'expected' => $expected,
            'unjustified' => $unjustified,
            'lates' => count($lates),
            'lateJustified' => (bool) ($lastLate['justified'] ?? false),
            'lateNote' => $lastLate
                ? $lastLate['date'].($lastLate['justified'] ? ' (Justificada)' : ' (Sin justificar)')
                : 'Sin tardanzas este mes',
        ];
    }

    private function punctuality(array $rows): float
    {
        if ($rows === []) {
            return 100.0;
        }
        $onTime = count(array_filter($rows, fn ($row) => $row['status'] === 'puntual'));

        return round($onTime / count($rows) * 100, 1);
    }

    private function weekBuckets(array $rows): array
    {
        $buckets = [];
        foreach ($rows as $row) {
            $monday = (new \DateTimeImmutable($row['iso']))->modify('monday this week');
            $key = $monday->format('Y-m-d');
            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => $this->shortDate($monday), 'puntual' => 0, 'total' => 0, 'lates' => 0, 'present' => 0];
            }
            $buckets[$key]['total']++;
            if ($row['status'] === 'puntual') {
                $buckets[$key]['puntual']++;
            }
            if ($row['status'] === 'tardanza') {
                $buckets[$key]['lates']++;
            }
            if (in_array($row['status'], ['puntual', 'tardanza'], true)) {
                $buckets[$key]['present']++;
            }
        }
        ksort($buckets);
        $series = [];
        $table = [];
        foreach (array_slice($buckets, -8, 8, true) as $bucket) {
            $pct = $bucket['total'] ? round($bucket['puntual'] / $bucket['total'] * 100, 1) : 100;
            $series[] = ['label' => $bucket['label'], 'value' => $pct, 'display' => $pct.'%'];
            $table[] = [$bucket['label'], $pct.'%', (string) $bucket['present'], (string) $bucket['lates']];
        }

        return [$series, $table];
    }

    private function daySchedule(\DateTimeImmutable $now, array $config): array
    {
        $dow = (int) $now->format('N');
        $items = $this->schedules()[$dow] ?? [];
        $nowMin = $this->toMinutes($now->format('H:i'));
        $nextAssigned = false;
        $classes = [];
        $count = 0;

        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'recess') {
                $classes[] = ['recess' => $item['label']];
                continue;
            }
            $count++;
            $start = $this->toMinutes($item['start']);
            $end = $this->toMinutes($item['end']);
            $done = $nowMin >= $end;
            $inProgress = $nowMin >= $start && $nowMin < $end;
            $next = ! $done && ! $nextAssigned;
            if ($next) {
                $nextAssigned = true;
            }
            $hour = (int) substr($item['start'], 0, 2);
            $classes[] = [
                'start' => $item['start'],
                'period' => $hour >= 12 ? 'PM' : 'AM',
                'name' => $item['name'],
                'place' => $item['place'],
                'range' => $item['start'].' - '.$item['end'],
                'note' => $done ? 'Finalizada' : ($inProgress ? 'En curso' : $item['note']),
                'next' => $next,
                'done' => $done,
                'badge' => $inProgress ? 'En curso' : ($next ? 'Próxima Clase' : null),
            ];
        }

        return ['classes' => $classes, 'count' => $count];
    }

    private function weeklySchedule(): array
    {
        $days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];
        $week = [];
        foreach ($days as $dow => $label) {
            $items = [];
            foreach ($this->schedules()[$dow] as $item) {
                if (($item['type'] ?? '') === 'recess') {
                    continue;
                }
                $items[] = $item['start'].' · '.$item['name'];
            }
            $week[] = ['day' => $label, 'items' => $items];
        }

        return $week;
    }

    private function schedules(): array
    {
        $recess = ['type' => 'recess', 'label' => '11:00 AM - 11:30 AM • Receso General & Refrigerio'];

        return [
            1 => [
                ['start' => '08:00', 'end' => '09:30', 'name' => 'Física Elemental', 'place' => 'Laboratorio 3 (Pabellón B) • Prof. Héctor Vega', 'note' => '90 minutos'],
                ['start' => '09:30', 'end' => '11:00', 'name' => 'Matemáticas Avanzadas', 'place' => 'Aula 402 • Prof. Lucía Santander', 'note' => 'Evaluación corta'],
                $recess,
                ['start' => '11:30', 'end' => '13:00', 'name' => 'Literatura Universal & Redacción', 'place' => 'Aula 402 • Prof. Fernando Quiroga', 'note' => '90 minutos'],
                ['start' => '13:00', 'end' => '14:30', 'name' => 'Historia del Perú', 'place' => 'Aula 402 • Prof. Jorge Palacios', 'note' => '90 minutos'],
            ],
            2 => [
                ['start' => '08:00', 'end' => '09:30', 'name' => 'Química', 'place' => 'Laboratorio 1 • Prof. Elena Ríos', 'note' => '90 minutos'],
                ['start' => '09:30', 'end' => '11:00', 'name' => 'Geometría', 'place' => 'Aula 402 • Prof. Lucía Santander', 'note' => '90 minutos'],
                $recess,
                ['start' => '11:30', 'end' => '13:00', 'name' => 'Inglés', 'place' => 'Aula 210 • Prof. Ana Beltrán', 'note' => '90 minutos'],
                ['start' => '13:00', 'end' => '14:30', 'name' => 'Educación Física', 'place' => 'Cancha • Prof. Luis Cárdenas', 'note' => '90 minutos'],
            ],
            3 => [
                ['start' => '08:00', 'end' => '09:30', 'name' => 'Biología', 'place' => 'Laboratorio 2 • Prof. Elena Ríos', 'note' => '90 minutos'],
                ['start' => '09:30', 'end' => '11:00', 'name' => 'Comunicación', 'place' => 'Aula 402 • Prof. Fernando Quiroga', 'note' => '90 minutos'],
                $recess,
                ['start' => '11:30', 'end' => '13:00', 'name' => 'Arte', 'place' => 'Taller de Arte • Prof. Mia Salazar', 'note' => '90 minutos'],
                ['start' => '13:00', 'end' => '14:30', 'name' => 'Matemática', 'place' => 'Aula 402 • Prof. Lucía Santander', 'note' => '90 minutos'],
            ],
            4 => [
                ['start' => '08:00', 'end' => '09:30', 'name' => 'Física Elemental', 'place' => 'Laboratorio 3 (Pabellón B) • Prof. Héctor Vega', 'note' => '90 minutos'],
                ['start' => '09:30', 'end' => '11:00', 'name' => 'Historia del Perú', 'place' => 'Aula 402 • Prof. Jorge Palacios', 'note' => '90 minutos'],
                $recess,
                ['start' => '11:30', 'end' => '13:00', 'name' => 'Inglés', 'place' => 'Aula 210 • Prof. Ana Beltrán', 'note' => '90 minutos'],
                ['start' => '13:00', 'end' => '14:30', 'name' => 'Tutoría', 'place' => 'Aula 402 • Prof. R. Morales', 'note' => '90 minutos'],
            ],
            5 => [
                ['start' => '08:00', 'end' => '09:30', 'name' => 'Matemática', 'place' => 'Aula 402 • Prof. Lucía Santander', 'note' => '90 minutos'],
                ['start' => '09:30', 'end' => '11:00', 'name' => 'Literatura', 'place' => 'Aula 402 • Prof. Fernando Quiroga', 'note' => '90 minutos'],
                $recess,
                ['start' => '11:30', 'end' => '13:00', 'name' => 'Química', 'place' => 'Laboratorio 1 • Prof. Elena Ríos', 'note' => '90 minutos'],
                ['start' => '13:00', 'end' => '14:30', 'name' => 'Educación Cívica', 'place' => 'Aula 210 • Prof. Jorge Palacios', 'note' => '90 minutos'],
            ],
        ];
    }

    private function shift(\DateTimeImmutable $now, array $config): array
    {
        if ((int) $now->format('N') >= 6) {
            return ['label' => 'Fin de semana', 'detail' => 'La próxima jornada abre el lunes'];
        }
        $nowMin = $this->toMinutes($now->format('H:i'));
        $limit = $this->toMinutes($config['entry_limit']);
        $exit = $this->toMinutes($config['exit_time']);
        $limitLabel = $this->clock12($config['entry_limit']);
        $exitLabel = $this->clock12($config['exit_time']);
        if ($nowMin < $limit) {
            return ['label' => 'Jornada por iniciar', 'detail' => 'Límite regular: '.$limitLabel.' (faltan '.($limit - $nowMin).' min)'];
        }
        if ($nowMin < $exit) {
            return ['label' => 'Jornada en curso', 'detail' => 'Ingreso límite '.$limitLabel.' • Salida '.$exitLabel];
        }

        return ['label' => 'Jornada finalizada', 'detail' => 'Salida regular: '.$exitLabel];
    }

    private function issueToken(int $userId, bool $force): array
    {
        return $this->mutate(function (array &$state) use ($userId, $force) {
            $current = $state['tokens'][(string) $userId] ?? null;
            if (! $force && $current && (int) $current['expires_at'] > time()) {
                $current['expires_in'] = max(1, (int) $current['expires_at'] - time());

                return $current;
            }
            $value = strtoupper(bin2hex(random_bytes(8)));
            $token = [
                'value' => $value,
                'hint' => substr($value, -4),
                'expires_in' => 60,
                'expires_at' => time() + 60,
            ];
            $state['tokens'][(string) $userId] = $token;

            return $token;
        });
    }

    private function openCases(array $state): array
    {
        return array_values(array_filter(
            $this->caseDefinitions(),
            fn (array $case) => ! in_array($case['id'], $state['resolved_cases'] ?? [], true)
        ));
    }

    private function caseDefinitions(): array
    {
        return [
            $this->case('sofia', 6, '07:48 AM (Molinete 01)', [
                'safe' => false,
                'bar' => 'bg-error',
                'overlayIcon' => 'wrong_location',
                'overlayColor' => 'text-error',
                'badge' => ['label' => 'Falla GPS: Fuera de Radio', 'box' => 'bg-error-container text-on-error-container', 'dot' => 'bg-error'],
                'diag' => ['icon' => 'location_off', 'color' => 'text-error', 'title' => 'Anomalía de geocerca:', 'text' => 'El dispositivo reportó coordenadas a 350 m del perímetro por rebote de la red móvil.'],
                'evidence' => ['icon' => 'verified', 'title' => 'Verificación del auxiliar:', 'text' => 'La alumna está en el control y presenta su carnet físico.'],
                'secondary' => ['kind' => 'retry', 'label' => 'Reintentar', 'icon' => 'refresh'],
                'approve' => ['label' => 'Aprobar ingreso manual', 'icon' => 'check_circle', 'tone' => 'secondary', 'reason' => 'Falla GPS (Cobertura Móvil)'],
            ]),
            $this->case('rodrigo', 7, '07:49 AM (Molinete 02)', [
                'safe' => true,
                'safeReason' => 'QR expirado resuelto',
                'bar' => 'bg-tertiary',
                'overlayIcon' => 'hourglass_disabled',
                'overlayColor' => 'text-tertiary',
                'badge' => ['label' => 'QR Expirado / TOTP Desync', 'box' => 'bg-tertiary-fixed text-on-tertiary-fixed', 'dot' => 'bg-tertiary'],
                'diag' => ['icon' => 'sync_problem', 'color' => 'text-tertiary', 'title' => 'Token inválido:', 'text' => 'El código dinámico de 60 s caducó durante el paso por el torniquete.'],
                'evidence' => ['icon' => 'contactless', 'title' => 'Alternativa:', 'text' => 'Se puede regenerar el pase en el tótem o autorizar el ingreso del auxiliar.'],
                'secondary' => ['kind' => 'regenerate', 'label' => 'Regenerar en tótem', 'icon' => 'point_of_sale'],
                'approve' => ['label' => 'Aprobar ingreso manual', 'icon' => 'check_circle', 'tone' => 'secondary', 'reason' => 'QR Expirado / Re-sync'],
            ]),
            $this->case('valentina', 8, '07:51 AM (Mesa Auxiliar)', [
                'safe' => false,
                'bar' => 'bg-error',
                'overlayIcon' => 'battery_alert',
                'overlayColor' => 'text-error',
                'badge' => ['label' => 'Sin smartphone / batería 0%', 'box' => 'bg-error-container text-on-error-container', 'dot' => 'bg-error'],
                'diag' => ['icon' => 'mobile_friendly', 'color' => 'text-error', 'title' => 'Sin terminal digital:', 'text' => 'La alumna no tiene teléfono hoy. Muestra el carnet físico 2026.'],
                'evidence' => ['select' => true],
                'approve' => ['label' => 'Aprobar ingreso por DNI', 'icon' => 'verified_user', 'tone' => 'secondary', 'reason' => null],
            ]),
            $this->case('lucas', 9, '07:53 AM (Molinete Familiar)', [
                'safe' => true,
                'safeReason' => 'Tardanza médica aprobada',
                'bar' => 'bg-primary',
                'overlayIcon' => 'medical_services',
                'overlayColor' => 'text-primary',
                'badge' => ['label' => 'Tardanza médica pre-justificada', 'box' => 'bg-primary-fixed text-on-primary-fixed', 'dot' => 'bg-primary'],
                'diag' => ['icon' => 'receipt_long', 'color' => 'text-primary', 'title' => 'El tutor adjuntó receta:', 'text' => 'Constancia pediátrica cargada a las 07:15 por la madre.'],
                'evidence' => ['attachment' => 'Certificado_Pediatrico.pdf', 'attachmentNote' => 'Dr. Andrés Morales - CMP 49102'],
                'approve' => ['label' => 'Validar ingreso con justificación', 'icon' => 'verified', 'tone' => 'primary', 'reason' => 'Tardanza justificada médica'],
            ]),
        ];
    }

    private function case(string $id, int $studentId, string $attempt, array $extra): array
    {
        $student = $this->requireStudent($studentId);

        return array_merge([
            'id' => $id,
            'name' => $student['name'],
            'short' => $student['shortName'],
            'grade' => $student['gradeLong'],
            'gradeShort' => $student['short'],
            'code' => $student['code'],
            'dni' => $student['dni'],
            'img' => $student['img'],
            'attempt' => $attempt,
        ], $extra);
    }

    private function baseNotifications(array $state, array $actor): array
    {
        $open = count($this->openCases($state));
        $limit = $this->clock12($state['config']['entry_limit']);

        return [
            ['id' => 'base-student-entry', 'role' => 'student', 'title' => 'Jornada de hoy', 'body' => 'Tu credencial QR se renueva cada 60 segundos. Límite de ingreso: '.$limit.'.', 'at' => 'Hoy'],
            ['id' => 'base-parent-live', 'role' => 'parent', 'title' => 'Canal familiar activo', 'body' => 'Las marcaciones de tus hijos vinculados aparecen en alertas y en el reporte.', 'at' => 'Hoy'],
            ['id' => 'base-staff-queue', 'role' => 'staff', 'title' => 'Cola de puerta', 'body' => $open === 0 ? 'No hay casos pendientes de revisión.' : $open.' caso(s) esperan resolución presencial.', 'at' => 'Ahora'],
        ];
    }

    private function catalog(): array
    {
        return [
            1 => $this->student(1, 'Mateo Valenzuela', 'Mateo', '4to Secundaria B', '4to Secundaria • Sección B', '4to Sec B', 'STU-88219', '71405255', 2, 'studentMateo', 'childMateo', 'childMateoList', 'mateo', 'Prof. R. Morales', null),
            4 => $this->student(4, 'Lucía Valenzuela', 'Lucía', '1ro Secundaria A', '1ro Secundaria • Sección A', '1ro Sec A', 'STU-77102', '70112233', 2, null, null, null, null, 'Prof. R. Morales', null),
            5 => $this->student(5, 'Diego Valenzuela', 'Diego', '2do Primaria C', '2do Primaria • Sección C', '2do Prim C', 'PRI-2204', '70998877', 2, null, null, null, null, 'Prof. A. Quispe', '13:00'),
            6 => $this->student(6, 'Sofía Morales Vega', 'Sofía Morales', '5to Secundaria A', '5to Secundaria - Sección A', '5to Sec A', 'SEC-5021', '74829104', null, null, null, null, 'sofia', 'Prof. H. Vega', null),
            7 => $this->student(7, 'Rodrigo Mendoza Castro', 'Rodrigo Mendoza', '3ro Secundaria C', '3ro Secundaria - Sección C', '3ro Sec C', 'SEC-3108', '71294812', null, null, null, null, 'rodrigo', 'Prof. L. Santander', null),
            8 => $this->student(8, 'Valentina Ramos Quispe', 'Valentina Ramos', '2do Secundaria B', '2do Secundaria - Sección B', '2do Sec B', 'SEC-2419', '76390145', null, null, null, null, 'valentina', 'Prof. F. Quiroga', null),
            9 => $this->student(9, 'Lucas Fernández Gómez', 'Lucas Fernández', '4to Primaria A', '4to Primaria - Sección A', '4to Prim A', 'PRI-4092', '89310492', null, null, null, null, 'lucas', 'Prof. A. Quispe', '13:00'),
            10 => $this->student(10, 'Ana Ruiz Palma', 'Ana Ruiz', '4to Secundaria A', '4to Secundaria - Sección A', '4to Sec A', 'SEC-5040', '78945612', null, null, null, null, null, 'Prof. R. Morales', null),
        ];
    }

    private function student(int $id, string $name, string $shortName, string $grade, string $gradeLong, string $short, string $code, string $dni, ?int $guardianId, ?string $avatar, ?string $childAvatar, ?string $listAvatar, ?string $img, string $tutor, ?string $exit): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'shortName' => $shortName,
            'grade' => $grade,
            'gradeLong' => $gradeLong,
            'short' => $short,
            'code' => $code,
            'dni' => $dni,
            'guardianId' => $guardianId,
            'avatar' => $avatar,
            'childAvatar' => $childAvatar,
            'listAvatar' => $listAvatar,
            'img' => $img,
            'tutor' => $tutor,
            'shift' => 'Turno Mañana',
            'exit' => $exit,
        ];
    }

    private function requireStudent(int $id): array
    {
        $student = $this->catalog()[$id] ?? null;
        if (! $student) {
            throw new \RuntimeException('No encontramos al estudiante.');
        }

        return $student;
    }

    private function findStudent(string $query): ?array
    {
        $q = trim($query);
        $digits = preg_replace('/\D/', '', $q) ?? '';
        foreach ($this->catalog() as $student) {
            if ($student['dni'] === $q || strcasecmp($student['code'], $q) === 0 || ($digits !== '' && $student['dni'] === $digits)) {
                return $student;
            }
            if (mb_strlen($q) >= 4 && mb_stripos($student['name'], $q) !== false) {
                return $student;
            }
        }

        return null;
    }

    private function account(string $dni, string $role): ?array
    {
        foreach (DemoAccounts::all() as $account) {
            if ($account['dni'] === $dni && $account['role'] === $role) {
                return $account;
            }
        }

        return null;
    }

    private function justificationFor(array $state, int $studentId, string $iso): ?array
    {
        foreach (array_reverse($state['justifications']) as $item) {
            if ((int) $item['child_id'] === $studentId && $item['event_date'] === $iso) {
                return $item;
            }
        }

        return null;
    }

    private function linkedIds(array $state, int $parentId): array
    {
        return array_map('intval', $state['linked'][(string) $parentId] ?? []);
    }

    private function rowOn(array $rows, string $iso): ?array
    {
        foreach ($rows as $row) {
            if ($row['iso'] === $iso) {
                return $row;
            }
        }

        return null;
    }

    private function guard(array $actor, string $role): void
    {
        if (($actor['role'] ?? '') !== $role) {
            throw new \RuntimeException('No tiene acceso a este panel.');
        }
    }

    private function schoolDays(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $days = [];
        $cursor = $from->setTime(0, 0);
        $end = $to->setTime(0, 0);
        while ($cursor <= $end) {
            if ((int) $cursor->format('N') <= 5) {
                $days[] = $cursor;
            }
            $cursor = $cursor->modify('+1 day');
        }

        return $days;
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('America/Lima'));
    }

    private function greeting(\DateTimeImmutable $now): string
    {
        $hour = (int) $now->format('H');
        if ($hour < 12) {
            return 'Buenos días';
        }

        return $hour < 19 ? 'Buenas tardes' : 'Buenas noches';
    }

    private function longDate(\DateTimeImmutable $date): string
    {
        $week = ucfirst(self::WEEKDAYS[(int) $date->format('N')]);

        return $week.', '.$date->format('j').' de '.self::MONTHS[(int) $date->format('n')].' de '.$date->format('Y');
    }

    private function shortDate(\DateTimeImmutable $date): string
    {
        return $date->format('j').' '.ucfirst(self::MONTHS_SHORT[(int) $date->format('n')]);
    }

    private function monthName(\DateTimeImmutable $date): string
    {
        return ucfirst(self::MONTHS[(int) $date->format('n')]);
    }

    private function toMinutes(string $hm): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hm, 0, 5)));

        return $h * 60 + $m;
    }

    private function clock12(string $hm): string
    {
        $mins = $this->toMinutes($hm);
        $h24 = intdiv($mins, 60);
        $m = $mins % 60;
        $suffix = $h24 >= 12 ? 'PM' : 'AM';
        $h12 = $h24 % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }

        return sprintf('%02d:%02d %s', $h12, $m, $suffix);
    }

    private function freshState(): array
    {
        return [
            'passwords' => [],
            'recovery' => [],
            'sessions' => [],
            'tokens' => [],
            'justifications' => [],
            'notifications' => [],
            'read_notifications' => [],
            'resolved_cases' => [],
            'linked' => ['2' => [1, 4]],
            'manual_count' => 7,
            'config' => [
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
            ],
            'audit' => [
                ['id' => 'a1', 'time' => '07:44:12 AM', 'name' => 'Mateo Salazar Peña', 'grade' => '1ro Secundaria B', 'reason' => 'Olvido de smartphone (carnet físico)', 'dot' => 'bg-tertiary', 'notif' => ['sms', 'SMS enviado'], 'status' => 'Ingresó', 'operator' => 'Aux. Ricardo Paz', 'fresh' => null],
                ['id' => 'a2', 'time' => '07:41:05 AM', 'name' => 'Camila Torres Ríos', 'grade' => '6to Primaria C', 'reason' => 'Error de lector RFID Puerta B', 'dot' => 'bg-error', 'notif' => ['notifications_active', 'Push app'], 'status' => 'Ingresó', 'operator' => 'Aux. Ricardo Paz', 'fresh' => null],
                ['id' => 'a3', 'time' => '07:38:50 AM', 'name' => 'Joaquín Silva Arce', 'grade' => '4to Secundaria A', 'reason' => 'Pase de transporte escolar demorado', 'dot' => 'bg-primary', 'notif' => ['mark_email_read', 'Email tutor'], 'status' => 'Ingresó', 'operator' => 'Aux. Ricardo Paz', 'fresh' => null],
            ],
        ];
    }

    private function state(): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return $this->mutate(function (array &$state) {
                return $state;
            });
        }
        $data = json_decode((string) file_get_contents($path), true);

        return $this->hydrate(is_array($data) ? $data : []);
    }

    private function hydrate(array $data): array
    {
        $base = $this->freshState();
        if (! isset($data['config']) || ! is_array($data['config'])) {
            return $base;
        }
        foreach ($data as $key => $value) {
            if ($key === 'config' && is_array($value)) {
                $base['config'] = array_merge($base['config'], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function mutate(callable $fn): mixed
    {
        $path = $this->path();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $handle = fopen($path, 'c+');
        if (! $handle) {
            throw new \RuntimeException('No se pudo abrir el estado de asistencia.');
        }
        try {
            if (! flock($handle, LOCK_EX)) {
                throw new \RuntimeException('No se pudo bloquear el estado de asistencia.');
            }
            $raw = stream_get_contents($handle);
            $decoded = json_decode($raw ?: '', true);
            $state = $this->hydrate(is_array($decoded) ? $decoded : []);
            $result = $fn($state);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($handle);

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function path(): string
    {
        return storage_path('app/geoeduca-state.json');
    }
}
