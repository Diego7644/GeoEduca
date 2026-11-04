<?php

declare(strict_types=1);

namespace Tests\Funcional;

use App\Support\InstitutionIdentity;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

final class InstitucionTest extends TestCase
{
    #[TestDox('La identidad oficial es la secundaria Mariscal Castilla de Huancayo')]
    public function test_la_identidad_no_es_otro_colegio(): void
    {
        $this->assertSame('0372599', InstitutionIdentity::MODULAR_CODE);
        $this->assertSame('Secundaria', InstitutionIdentity::LEVEL);
        $this->assertStringContainsString('El Tambo', InstitutionIdentity::ADDRESS);
        $this->assertStringContainsString('Huancayo', InstitutionIdentity::ADDRESS);
        $this->assertFalse(InstitutionIdentity::isForeignDemo(InstitutionIdentity::NAME, InstitutionIdentity::ADDRESS));
        $this->assertTrue(InstitutionIdentity::isForeignDemo('Colegio San Gabriel', 'Av. Las Palmeras 450, Urb. Monterrico'));
        $this->assertNull(InstitutionIdentity::card()['crest']);
        $this->assertNull(InstitutionIdentity::settingsDefaults()['lat']);
    }
}
