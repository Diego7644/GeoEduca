<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestDox;

final class EsquemaTest extends PersistenciaTestCase
{
    #[TestDox('migrate:fresh crea las tablas de F1.1')]
    public function test_las_tablas_existen_tras_migrar(): void
    {
        foreach ([
            'users',
            'personal_access_tokens',
            'staff_members',
            'students',
            'guardian_student',
            'school_settings',
            'attendance_records',
            'justifications',
            'qr_tokens',
            'in_app_notifications',
            'password_reset_codes',
            'audit_logs',
        ] as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        }
    }
}
