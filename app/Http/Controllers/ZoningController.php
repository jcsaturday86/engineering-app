<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ZoningAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ZoningController extends Controller
{
    public function index()
    {
        $applications = Application::with('permitType')
            ->where('status', 'submitted')
            ->whereHas('permitType', fn ($q) => $q->where('code', 'BP'))
            ->latest()
            ->paginate(20);

        return view('zoning.index', compact('applications'));
    }

    public function assess(Application $application)
    {
        $zoningAssessment = $application->zoningAssessment ?? new ZoningAssessment();

        return view('zoning.assess', compact('application', 'zoningAssessment'));
    }

    public function store(Request $request, Application $application)
    {
        $validated = $request->validate([
            'project_lifespan' => 'nullable|string|max:255',
            'project_significance' => 'nullable|string|max:255',
            'project_classification' => 'nullable|string|max:255',
            'site_zoning_classification' => 'nullable|string|max:255',
            'right_over_lands' => 'nullable|string|max:255',
            'radius_covered' => 'nullable|string|max:255',
            'land_use_radius' => 'nullable|string|max:255',
            'findings_evaluation' => 'nullable|string',
            'decision_recommended' => 'nullable|string',
            'date_evaluation' => 'nullable|date',
            'project_status' => 'nullable|string|max:255',
            'boundary_north' => 'nullable|string|max:255',
            'boundary_south' => 'nullable|string|max:255',
            'boundary_east' => 'nullable|string|max:255',
            'boundary_west' => 'nullable|string|max:255',
            'building_coverage' => 'nullable|string|max:255',
            'secure_ecc' => 'boolean',
            'off_street_parking' => 'boolean',
        ]);

        $validated['application_id'] = $application->id;
        $validated['assessed_by'] = Auth::id();

        ZoningAssessment::updateOrCreate(
            ['application_id' => $application->id],
            $validated
        );

        return back()->with('success', 'Zoning assessment saved.');
    }

    public function finalize(Application $application)
    {
        if ($application->status === 'submitted') {
            $application->update(['status' => 'zoning_assessed']);
            activity()->causedBy(Auth::user())->performedOn($application)->log('Zoning assessment finalized');
        }

        return redirect()->route('zoning.index')->with('success', 'Zoning assessment finalized.');
    }

    public function skip(Application $application)
    {
        if ($application->status === 'submitted') {
            $application->update(['status' => 'zoning_assessed']);
            activity()->causedBy(Auth::user())->performedOn($application)->log('Zoning assessment skipped');
        }

        return redirect()->route('zoning.index')->with('success', 'Zoning assessment skipped.');
    }
}
