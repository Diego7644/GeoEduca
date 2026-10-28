<?php

namespace App\Support;

use App\Models\SchoolSetting;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;

/**
 * Grafo mínimo para pruebas y para no confundir users.id con students.id.
 * El apoderado de demostración se vincula por DNI, no por el id 2 del prototipo JSON.
 */
class PrototypeGraph
{
    public static function ensure(): void
    {
        SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            [
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
            ]
        );

        $studentUser = User::query()->where('dni', '71405255')->where('role', User::ROLE_STUDENT)->first();
        $parent = User::query()->where('dni', '71405255')->where('role', User::ROLE_PARENT)->first();
        $staff = User::query()->where('dni', '40111222')->where('role', User::ROLE_STAFF)->first();

        $auxiliar = null;
        if ($staff) {
            $auxiliar = StaffMember::query()->updateOrCreate(
                ['user_id' => $staff->id],
                ['name' => $staff->name, 'kind' => StaffMember::KIND_AUXILIAR]
            );
        }

        $mateo = Student::query()->updateOrCreate(
            ['dni' => '71405255'],
            [
                'user_id' => $studentUser?->id,
                'homeroom_staff_id' => null,
                'code' => 'STU-88219',
                'name' => 'Mateo Valenzuela',
                'short_name' => 'Mateo',
                'grade' => '4to Secundaria B',
                'grade_long' => '4to Secundaria • Sección B',
                'grade_short' => '4to Sec B',
                'shift' => 'Turno Mañana',
            ]
        );

        Student::query()->updateOrCreate(
            ['dni' => '74829104'],
            [
                'user_id' => null,
                'code' => 'SEC-5021',
                'name' => 'Sofía Morales Vega',
                'short_name' => 'Sofía',
                'grade' => '5to Secundaria A',
                'grade_long' => '5to Secundaria - Sección A',
                'grade_short' => '5to Sec A',
                'shift' => 'Turno Mañana',
            ]
        );

        if ($parent) {
            $parent->wards()->syncWithoutDetaching([$mateo->id]);
        }
    }
}
