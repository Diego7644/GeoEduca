<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Unit;

use App\Auth\DemoAccounts;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Caracterización: cuentas de demostración (DemoAccounts). Documenta limitaciones conocidas.
 */
#[Group('complementaria')]
final class DemoAccountsTest extends TestCase
{
    #[TestDox('Hay exactamente tres cuentas, una por perfil: student, parent y staff')]
    public function test_tres_cuentas_una_por_perfil(): void
    {
        $perfiles = array_column(DemoAccounts::all(), 'role');
        sort($perfiles);

        $this->assertSame(['parent', 'staff', 'student'], $perfiles);
    }

    #[TestDox('Todos los DNI tienen 8 dígitos y los identificadores internos son únicos')]
    public function test_dni_de_8_digitos_e_ids_unicos(): void
    {
        $cuentas = DemoAccounts::all();

        foreach ($cuentas as $cuenta) {
            $this->assertMatchesRegularExpression('/^\d{8}$/', $cuenta['dni']);
        }

        $ids = array_column($cuentas, 'id');
        $this->assertSame($ids, array_values(array_unique($ids)));
    }

    #[TestDox('LIMITACIÓN: dos perfiles distintos comparten el mismo DNI (se distinguen por perfil y contraseña)')]
    public function test_dos_perfiles_comparten_dni(): void
    {
        $repeticiones = array_count_values(array_column(DemoAccounts::all(), 'dni'));

        $this->assertSame(2, max($repeticiones));
    }

    #[TestDox('LIMITACIÓN: las contraseñas están en texto plano (ninguna parece un hash bcrypt/argon)')]
    public function test_las_contrasenas_no_estan_hasheadas(): void
    {
        foreach (DemoAccounts::all() as $cuenta) {
            $this->assertDoesNotMatchRegularExpression('/^\$(2y|argon2i|argon2id)\$/', $cuenta['password']);
        }
    }
}
