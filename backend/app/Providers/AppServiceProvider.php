<?php

namespace App\Providers;

use App\Auth\MockUserAuthenticator;
use App\Contracts\UserAuthenticator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Cambiar MockUserAuthenticator por EloquentUserAuthenticator al conectar la BD.
        $this->app->bind(UserAuthenticator::class, MockUserAuthenticator::class);
    }

    public function boot(): void
    {
        //
    }
}
