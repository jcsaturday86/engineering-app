<?php

namespace App\Http\Controllers;

use App\Models\AnnualInspectionApplication;
use App\Models\Application;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\SignageApplication;
use App\Support\ApplicationTimeline;

/**
 * Staff-side counterpart to OnlineApplicationController::track() — same
 * "View full history" destination the client portal already has, reused
 * here instead of duplicated, just without the client ownership check
 * (staff can view any application, gated by the view-applications
 * permission on the route instead).
 */
class ApplicationHistoryController extends Controller
{
    private const MODELS = [
        'BP' => Application::class,
        'OP' => OccupancyApplication::class,
        'FP' => FencingApplication::class,
        'DP' => DemolitionApplication::class,
        'SGP' => SignageApplication::class,
        'AI' => AnnualInspectionApplication::class,
    ];

    private const SHOW_ROUTES = [
        'BP' => 'applications.show',
        'OP' => 'occupancy-applications.show',
        'FP' => 'fencing-applications.show',
        'DP' => 'demolition-applications.show',
        'SGP' => 'signage-applications.show',
        'AI' => 'annual-inspection-applications.show',
    ];

    public function track(string $type, int $id)
    {
        $type = strtoupper($type);
        abort_unless(isset(self::MODELS[$type]), 404);

        $model = self::MODELS[$type]::withTrashed()->findOrFail($id);
        $model->load('permits');

        $timeline = ApplicationTimeline::build($model, $type);
        $processedBy = ApplicationTimeline::processedBy($model);

        $application = $model;
        $applicationType = $type;
        $portal = 'staff';
        $backUrl = route(self::SHOW_ROUTES[$type], $model->id);

        return view('online.track', compact('application', 'applicationType', 'timeline', 'processedBy', 'portal', 'backUrl'));
    }
}
