<?php

namespace App\Providers;

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
        // Configure morph map for entity files
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'Academy' => \App\Models\Academy::class,
            'Area' => \App\Models\Area::class,
            'Service' => \App\Models\Service::class,
        ]);
    }
}
