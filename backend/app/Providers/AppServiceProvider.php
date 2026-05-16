<?php

namespace App\Providers;

use App\Services\ITSoloLevelingSecurity;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ITSoloLevelingSecurity::class, function () {
            return new ITSoloLevelingSecurity(
                (string) config('services.it_solo_leveling.signature', ''),
                [
                    // Keep defaults reasonable; can be tuned for your environment.
                    'memory_cost' => 1 << 17,
                    'time_cost' => 4,
                    'threads' => 2,
                ]
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
