<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Auth\RecoveryCodeGenerator;

final class FakeRecoveryCodeGenerator extends RecoveryCodeGenerator
{
    public function __construct(private string $code = '123456')
    {
    }

    public function generate(): string
    {
        return $this->code;
    }
}
