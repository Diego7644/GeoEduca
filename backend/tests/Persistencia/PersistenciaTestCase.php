<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PersistenciaTestCase extends TestCase
{
    use RefreshDatabase;
}
