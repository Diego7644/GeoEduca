<?php

namespace App\Support;

/**
 * Identidad verificada en Identicole (Padrón 2026), código modular 0372599.
 * Es la secundaria de El Tambo, Huancayo. No incluye escudo, director ni
 * coordenadas: las fuentes consultadas no coinciden o no publican ese dato.
 */
final class InstitutionIdentity
{
    public const NAME = 'Institución Educativa Mariscal Castilla';

    public const LEVEL = 'Secundaria';

    public const MODULAR_CODE = '0372599';

    public const LOCAL_CODE = '223246';

    public const UGEL = 'UGEL Huancayo';

    public const ADDRESS = 'Calle Arequipa cuadra 9 s/n, El Tambo, Huancayo, Junín, Perú';

    public const CAMPUS = 'El Tambo, Huancayo';

    public const SOURCE = 'Identicole, Padrón 2026';

    public static function card(): array
    {
        return [
            'name' => self::NAME,
            'level' => self::LEVEL,
            'modular_code' => self::MODULAR_CODE,
            'local_code' => self::LOCAL_CODE,
            'ugel' => self::UGEL,
            'address' => self::ADDRESS,
            'campus' => self::CAMPUS,
            'source' => self::SOURCE,
            'crest' => null,
        ];
    }

    public static function settingsDefaults(): array
    {
        return [
            'school' => self::NAME,
            'campus' => self::CAMPUS,
            'address' => self::ADDRESS,
            'year' => '2026',
            'entry_limit' => '08:00',
            'exit_time' => '14:30',
            'geofence_m' => 100,
            'promotion_min' => 85,
            'tolerance_min' => 10,
            'lat' => null,
            'lng' => null,
        ];
    }

    public static function isForeignDemo(?string $school, ?string $address): bool
    {
        $school = mb_strtolower((string) $school);
        $address = mb_strtolower((string) $address);

        return str_contains($school, 'san gabriel')
            || str_contains($address, 'palmeras')
            || str_contains($address, 'monterrico');
    }
}
