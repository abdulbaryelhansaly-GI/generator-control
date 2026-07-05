<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Generator;
use App\Observers\GeneratorObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Generator::observe(GeneratorObserver::class);
    }
}