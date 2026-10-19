<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        RateLimiter::for('registration', function (Request $request) {
            $tooMany = fn () => response()->json([
                'message' => 'נשלחו יותר מדי טפסים מהחיבור הזה. אפשר לנסות שוב מאוחר יותר.',
            ], 429);

            return [
                Limit::perMinute(3)->by($request->ip())->response($tooMany),
                Limit::perHour(config('registration.rate_per_hour'))->by($request->ip())->response($tooMany),
            ];
        });
    }
}
