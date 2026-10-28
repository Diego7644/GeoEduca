<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestDox;

final class UsuariosTest extends PersistenciaTestCase
{
    #[TestDox('La contraseña se guarda con hash, no en texto plano')]
    public function test_la_contrasena_se_guarda_con_hash(): void
    {
        $user = User::factory()->student()->create(['password' => 'clave-de-prueba-01']);

        $this->assertNotSame('clave-de-prueba-01', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('clave-de-prueba-01', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    #[TestDox('El mismo DNI puede existir en dos roles distintos')]
    public function test_el_dni_se_repite_si_el_rol_cambia(): void
    {
        User::factory()->student()->create(['dni' => '10000011']);
        $parent = User::factory()->parent()->create(['dni' => '10000011']);

        $this->assertSame('10000011', $parent->dni);
        $this->assertSame(2, User::query()->where('dni', '10000011')->count());
    }

    #[TestDox('El mismo DNI no puede repetirse en el mismo rol')]
    public function test_dni_y_rol_son_unicos(): void
    {
        User::factory()->student()->create(['dni' => '10000012']);

        $this->expectException(QueryException::class);
        User::factory()->student()->create(['dni' => '10000012']);
    }
}
