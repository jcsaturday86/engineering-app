<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\AnnualInspectionApplication;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\SignageApplication;
use App\Notifications\ApplicationReviewedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Engineering's approve/disapprove gate for applications submitted through
 * the client online portal. A client submission lands in `pending_approval`
 * (see OnlineApplicationController::submit()); this is the first place
 * `source = 'online'` is actually read anywhere in the app. Approving routes
 * the application into the normal staff queue (zoning for BP, straight to
 * engineering assessment for the rest); disapproving returns it to the
 * client as `returned` with remarks, so they can edit and resubmit.
 */
class ApplicationReviewController extends Controller
{
    private const TYPES = [
        'BP' => ['model' => Application::class, 'label' => 'Building Permit', 'show_route' => 'applications.show', 'uses_zoning' => true],
        'OP' => ['model' => OccupancyApplication::class, 'label' => 'Occupancy Permit', 'show_route' => 'occupancy-applications.show', 'uses_zoning' => false],
        'FP' => ['model' => FencingApplication::class, 'label' => 'Fencing Permit', 'show_route' => 'fencing-applications.show', 'uses_zoning' => false],
        'DP' => ['model' => DemolitionApplication::class, 'label' => 'Demolition Permit', 'show_route' => 'demolition-applications.show', 'uses_zoning' => false],
        'SGP' => ['model' => SignageApplication::class, 'label' => 'Signage Permit', 'show_route' => 'signage-applications.show', 'uses_zoning' => false],
        'AI' => ['model' => AnnualInspectionApplication::class, 'label' => 'Annual Inspection', 'show_route' => 'annual-inspection-applications.show', 'uses_zoning' => false],
    ];

    public static function pendingCount(): int
    {
        return collect(self::TYPES)->sum(fn ($meta) => $meta['model']::where('status', 'pending_approval')->count());
    }

    private function typeMeta(string $type): ?array
    {
        return self::TYPES[strtoupper($type)] ?? null;
    }

    public function index(Request $request)
    {
        $filterType = strtoupper((string) $request->query('type', ''));
        $applications = collect();

        foreach (self::TYPES as $code => $meta) {
            if ($filterType && $filterType !== $code) {
                continue;
            }

            $meta['model']::where('status', 'pending_approval')
                ->orderBy('submitted_at')
                ->get()
                ->each(function ($item) use ($code, $meta, &$applications) {
                    $applications->push((object) [
                        'type' => $code,
                        'type_label' => $meta['label'],
                        'id' => $item->id,
                        'application_number' => $item->application_number,
                        'applicant_full_name' => $item->applicant_full_name ?: ($item->owner_name ?? ''),
                        'submitted_at' => $item->submitted_at,
                        'show_route' => $meta['show_route'],
                    ]);
                });
        }

        $applications = $applications->sortBy('submitted_at')->values();
        $permitTypes = self::TYPES;

        return view('application-review.index', compact('applications', 'filterType', 'permitTypes'));
    }

    public function approve(Request $request, string $type, int $id)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $model = $meta['model']::findOrFail($id);

        $nextStatus = $meta['uses_zoning'] ? ApplicationStatus::FOR_ZONING_ASSESSMENT : ApplicationStatus::SUBMITTED;
        $currentStatus = ApplicationStatus::tryFrom($model->status);

        if (! $currentStatus || ! $currentStatus->canTransitionToFor($nextStatus, $type)) {
            return back()->with('error', 'Only applications awaiting approval can be approved.');
        }

        $model->update([
            'status' => $nextStatus->value,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'review_remarks' => null,
        ]);

        activity()->causedBy(Auth::user())->performedOn($model)
            ->log('Application approved by Engineering — routed to ' . ($meta['uses_zoning'] ? 'Planning Office for Zoning Assessment' : 'Engineering Assessment'));

        if ($model->clientUser) {
            $model->clientUser->notify(new ApplicationReviewedNotification($model, 'approved'));
        }

        return back()->with('success', "{$model->application_number} approved.");
    }

    public function disapprove(Request $request, string $type, int $id)
    {
        $request->validate([
            'password' => 'required|string',
            'review_remarks' => 'required|string|max:1000',
        ], [
            'review_remarks.required' => 'Please explain why this application is being returned.',
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $model = $meta['model']::findOrFail($id);

        $currentStatus = ApplicationStatus::tryFrom($model->status);

        if (! $currentStatus || ! $currentStatus->canTransitionToFor(ApplicationStatus::RETURNED, $type)) {
            return back()->with('error', 'Only applications awaiting approval can be returned.');
        }

        $model->update([
            'status' => 'returned',
            'review_remarks' => $request->input('review_remarks'),
            'approved_at' => null,
            'approved_by' => null,
        ]);

        activity()->causedBy(Auth::user())->performedOn($model)
            ->log('Application returned to client for revision: ' . $request->input('review_remarks'));

        if ($model->clientUser) {
            $model->clientUser->notify(new ApplicationReviewedNotification($model, 'returned', $request->input('review_remarks')));
        }

        return back()->with('success', "{$model->application_number} returned to the client for revision.");
    }
}
