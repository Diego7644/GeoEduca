<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * REQUISITO DE SEGURIDAD: una entrada hostil o malformada nunca provoca un error 500,
 * no emite token y no filtra detalles de base de datos ni trazas.
 *
 * Matriz manual: S-07 (inyección SQL) y AUTO-01 (hallazgo nuevo de la automatización).
 *
 * - test_entrada_hostil_se_rechaza_sin_error_500: requisito que el sistema SÍ cumple hoy.
 *   Si falla, es una regresión.
 * - test_identificador_no_textual_no_provoca_error_500: requisito INCUMPLIDO hoy (HTTP 500).
 *   Causa: LoginRequest::prepareForValidation() convierte con (string) un valor que puede ser
 *   un arreglo u objeto JSON ("Array to string conversion", app/Http/Requests/Auth/LoginRequest.php:22).
 *
 * Los datasets contienen SOLO los campos que cambian: la credencial base se añade dentro de la
 * prueba para que PHPUnit nunca imprima la contraseña de demostración en un informe de fallo.
 */
final class EntradasHostilesTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function cambiosHostiles(): array
    {
        return [
            'SQL en el DNI (OR 1=1)' => [['identifier' => "71405255' OR '1'='1"]],
            'SQL con comentario en el DNI' => [['identifier' => "admin'--"]],
            'SQL en la contraseña (OR 1=1)' => [['password' => "' OR '1'='1' --"]],
            'SQL con punto y coma en la contraseña' => [['password' => "x'; DROP TABLE users; --"]],
            'script HTML en la contraseña' => [['password' => '<script>alert(1)</script>']],
            'perfil con SQL' => [['role' => "student' OR '1'='1"]],
            'contraseña enviada como arreglo' => [['password' => ['a' => 'b']]],
            'perfil enviado como arreglo' => [['role' => ['student']]],
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    #[Group('S-07')]
    #[Group('requisito-cumplido')]
    #[DataProvider('cambiosHostiles')]
    #[TestDox('Una entrada hostil se rechaza con 401 o 422: sin error 500, sin token y sin detalles internos')]
    public function test_entrada_hostil_se_rechaza_sin_error_500(array $cambios): void
    {
        $this->assertRechazoLimpio($cambios);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function identificadoresNoTextuales(): array
    {
        return [
            'DNI enviado como arreglo' => [['identifier' => ['71405255']]],
            'DNI enviado como objeto' => [['identifier' => ['$ne' => '']]],
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    #[Group('AUTO-01')]
    #[Group('requisito-incumplido')]
    #[DataProvider('identificadoresNoTextuales')]
    #[TestDox('Un DNI enviado como arreglo u objeto se rechaza con 422, no con error 500')]
    public function test_identificador_no_textual_no_provoca_error_500(array $cambios): void
    {
        $this->assertRechazoLimpio($cambios);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function assertRechazoLimpio(array $cambios): void
    {
        $carga = array_merge(DemoCredentials::for('student'), $cambios);

        $respuesta = $this->postJson('/api/auth/login', $carga);

        $this->assertContains(
            $respuesta->getStatusCode(),
            [401, 422],
            'Una entrada hostil debe responder 401 o 422; respondió '.$respuesta->getStatusCode()
        );
        $respuesta->assertJsonMissingPath('token');
        $this->assertDoesNotMatchRegularExpression('/SQLSTATE|QueryException|PDOException|Stack trace/i', $respuesta->getContent());
    }
}
