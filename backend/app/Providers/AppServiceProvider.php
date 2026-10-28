<?php

namespace App\Providers;

use App\Auth\EloquentUserAuthenticator;
use App\Contracts\UserAuthenticator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserAuthenticator::class, EloquentUserAuthenticator::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $dni = $request->input('identifier', $request->input('credential', ''));
            $dni = is_scalar($dni) ? preg_replace('/\D/', '', (string) $dni) : 'invalid';

            return Limit::perMinute(5)->by($dni.'|'.$request->ip());
        });

        RateLimiter::for('recovery', function (Request $request) {
            $dni = $request->input('identifier', '');
            $dni = is_scalar($dni) ? preg_replace('/\D/', '', (string) $dni) : 'invalid';

            return Limit::perMinute(10)->by($dni.'|'.$request->ip());
        });
    }
}
