<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\OccupancyApplication;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
            'bp' => Application::class,
            'op' => OccupancyApplication::class,
        ]);
    }
}
