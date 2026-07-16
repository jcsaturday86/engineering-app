<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\SignageApplication;
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
            'dp' => DemolitionApplication::class,
            'sgp' => SignageApplication::class,
            'fp' => FencingApplication::class,
        ]);
    }
}
