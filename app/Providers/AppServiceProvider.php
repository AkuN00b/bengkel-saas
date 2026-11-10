<?php

namespace App\Providers;

use App\Models\Service;
use App\Models\SparePart;
use App\Models\User;
use App\Policies\CatalogPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Service::class, CatalogPolicy::class);
        Gate::policy(SparePart::class, CatalogPolicy::class);
        ResetPassword::createUrlUsing(fn (User $user, string $token) => rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));

        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('auth-requests', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('password-requests', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
