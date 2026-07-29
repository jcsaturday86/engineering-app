<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AnnualInspectionApplication;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\Permit;
use App\Models\PermitType;
use App\Models\Setting;
use App\Models\Signatory;
use App\Models\SignageApplication;
use App\Notifications\ApplicationSubmittedNotification;
use App\Models\User;
use App\Support\ApplicationTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class OnlineApplicationController extends Controller
{
    /**
     * Per-permit-type wiring for the client portal. Each staff module
     * (ApplicationController, OccupancyApplicationController, ...) owns its
     * own validation/persistence via persistApplication()/applyUpdate() and
     * its own form.blade.php — the portal reuses both instead of duplicating
     * ~3,850 lines of Blade and ~95 validation rules per type.
     */
    private const TYPES = [
        'BP' => [
            'model' => Application::class,
            'controller' => ApplicationController::class,
            'form_view' => 'applications.form',
            'show_view' => 'applications.show',
            'show_relations' => [
                'permitType', 'applicationType', 'scopeOfWork', 'formOfOwnership',
                'applicantProvince', 'applicantCity', 'applicantBarangay', 'buildingBarangay',
                'landClassification', 'applicationOccupancyGroups.occupancyGroup',
                'applicationOccupancyGroups.occupancySubGroup',
                'assessments.assessmentItems', 'billings', 'collections', 'permits',
            ],
            'needs_permit_type' => true,
            'occupancy_groups' => true,
            'pdf_template' => 'pdf.application-form',
        ],
        'OP' => [
            'model' => OccupancyApplication::class,
            'controller' => OccupancyApplicationController::class,
            'form_view' => 'occupancy-applications.form',
            'show_view' => 'occupancy-applications.show',
            'show_relations' => [
                'applicationType', 'formOfOwnership',
                'applicantProvince', 'applicantCity', 'applicantBarangay', 'buildingBarangay',
                'landClassification', 'applicationOccupancyGroups.occupancyGroup',
                'applicationOccupancyGroups.occupancySubGroup',
                'assessments.assessmentItems', 'billings', 'collections', 'permits',
            ],
            'needs_permit_type' => true,
            'occupancy_groups' => true,
            'pdf_template' => 'pdf.occupancy-application-form',
        ],
        'FP' => [
            'model' => FencingApplication::class,
            'controller' => FencingApplicationController::class,
            'form_view' => 'fencing-applications.form',
            'show_view' => 'fencing-applications.show',
            'show_relations' => [
                'formOfOwnership',
                'applicantProvince', 'applicantCity', 'applicantBarangay', 'constructionBarangay',
                'assessments.assessmentItems', 'billings', 'collections', 'permits',
            ],
            'needs_permit_type' => false,
            'occupancy_groups' => false,
            'pdf_template' => 'pdf.fencing-application-form',
        ],
        'DP' => [
            'model' => DemolitionApplication::class,
            'controller' => DemolitionApplicationController::class,
            'form_view' => 'demolition-applications.form',
            'show_view' => 'demolition-applications.show',
            'show_relations' => [
                'formOfOwnership',
                'applicantProvince', 'applicantCity', 'applicantBarangay', 'demolitionBarangay',
                'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup',
                'assessments.assessmentItems', 'billings', 'collections', 'permits',
            ],
            'needs_permit_type' => false,
            'occupancy_groups' => true,
            'pdf_template' => 'pdf.demolition-application-form',
        ],
        'SGP' => [
            'model' => SignageApplication::class,
            'controller' => SignageApplicationController::class,
            'form_view' => 'signage-applications.form',
            'show_view' => 'signage-applications.show',
            'show_relations' => [
                'applicantProvince', 'applicantCity', 'applicantBarangay',
                'assessments.assessmentItems', 'billings', 'collections', 'permits',
            ],
            'needs_permit_type' => false,
            'occupancy_groups' => false,
            'pdf_template' => 'pdf.signage-application-form',
        ],
        'AI' => [
            'model' => AnnualInspectionApplication::class,
            'controller' => AnnualInspectionApplicationController::class,
            'form_view' => 'annual-inspection-applications.form',
            'show_view' => 'annual-inspection-applications.show',
            'show_relations' => [
                'locationBarangay',
                'assessments.assessmentItems', 'billings', 'collections', 'permits',
                'annualInspectionPermitUnits.permit', 'equipmentItems',
                'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup',
            ],
            'needs_permit_type' => false,
            'occupancy_groups' => true,
            'pdf_template' => 'pdf.annual-inspection-application-form',
        ],
    ];

    private function typeMeta(string $type): ?array
    {
        return self::TYPES[strtoupper($type)] ?? null;
    }

    /** @return Application|OccupancyApplication|FencingApplication|DemolitionApplication|SignageApplication|AnnualInspectionApplication */
    private function resolveModel(string $type, int $id)
    {
        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $model = $meta['model']::findOrFail($id);
        abort_if($model->client_user_id !== Auth::id(), 403);

        return $model;
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $applications = collect();

        foreach (self::TYPES as $code => $meta) {
            $rows = $meta['model']::where('client_user_id', $user->id)->latest()->get();

            $applications = $applications->concat($rows->map(function ($app) use ($code) {
                $permit = $app->permits->sortByDesc('created_at')->first();
                $tatStart = $app->submitted_at ?? $app->created_at;
                $tatDays = $permit ? (int) floor($tatStart->diffInDays($permit->created_at, true)) : null;

                return (object) [
                    'id' => $app->id,
                    'type' => strtolower($code),
                    'application_number' => $app->application_number,
                    'applicant_full_name' => $app->applicant_full_name ?: ($app->owner_name ?? ''),
                    'permit_type_code' => $code,
                    'permit_type_name' => $app->permitType->name ?? PermitType::where('code', $code)->value('name') ?? $code,
                    'project_title' => $app->project_title ?? null,
                    'status' => $app->status,
                    'review_remarks' => $app->review_remarks,
                    'created_at' => $app->created_at,
                    'tat_days' => $tatDays,
                    'model' => $app,
                ];
            }));
        }

        $applications = $applications->sortByDesc('created_at')->values();

        $stats = [
            'total' => $applications->count(),
            'pending' => $applications->whereNotIn('status', ['cancelled', 'released', 'permit_generated'])->count(),
            'returned' => $applications->where('status', 'returned')->count(),
            'approved' => $applications->whereIn('status', ['permit_generated', 'released'])->count(),
        ];

        $statusFilter = $request->query('status');
        $statusOptions = $applications->pluck('status')->unique()->values();

        if ($statusFilter) {
            $applications = $applications->where('status', $statusFilter)->values();
        }

        return view('online.dashboard', compact('applications', 'stats', 'statusFilter', 'statusOptions'));
    }

    /**
     * Permit-type chooser (no ?type=) or the actual staff form.blade.php
     * rendered with portal-mode variables (see Phase 2 of the online-portal
     * rollout — every module's form.blade.php reads $portal/$formAction/
     * $indexUrl/$indexLabel/$homeUrl, defaulting to staff behaviour when
     * absent).
     */
    public function create(Request $request)
    {
        $type = strtoupper((string) $request->query('type', ''));
        $meta = $this->typeMeta($type);

        if (! $meta) {
            $permitTypes = PermitType::where('is_active', true)
                ->whereIn('code', array_keys(self::TYPES))
                ->get();

            return view('online.apply', compact('permitTypes'));
        }

        $ctrl = app($meta['controller']);
        $permitType = PermitType::where('code', $type)->where('is_active', true)->firstOrFail();

        $data = $meta['needs_permit_type'] ? $ctrl->getFormData($permitType->id) : $ctrl->getFormData();
        $data['application'] = null;
        if ($type === 'BP') {
            $data['permitType'] = $permitType;
        }

        return view($meta['form_view'], $this->portalViewVars($data, $type, route('online.store', ['type' => $type])));
    }

    public function store(Request $request)
    {
        $type = strtoupper((string) ($request->input('_type') ?? $request->query('type', '')));
        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $ctrl = app($meta['controller']);

        try {
            $application = $ctrl->persistApplication($request, [
                'status' => 'draft',
                'source' => 'online',
                'entered_by' => Auth::id(),
                'client_user_id' => Auth::id(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }

        return redirect()->route('online.show', ['type' => $type, 'id' => $application->id])
            ->with('success', "Application {$application->application_number} saved as draft. Review it and click Submit when you're ready.");
    }

    public function edit(string $type, int $id)
    {
        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $model = $this->resolveModel($type, $id);
        abort_unless(in_array($model->status, ['draft', 'returned'], true), 403);

        if ($meta['occupancy_groups']) {
            $model->load('applicationOccupancyGroups');
        }

        $ctrl = app($meta['controller']);
        $permitType = PermitType::where('code', $type)->where('is_active', true)->first();

        $data = $meta['needs_permit_type'] ? $ctrl->getFormData($permitType->id) : $ctrl->getFormData();
        $data['application'] = $model;
        if ($type === 'BP') {
            $data['permitType'] = $model->permitType;
        }

        return view($meta['form_view'], $this->portalViewVars($data, $type, route('online.update', ['type' => $type, 'id' => $model->id])));
    }

    public function update(Request $request, string $type, int $id)
    {
        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $model = $this->resolveModel($type, $id);
        abort_unless(in_array($model->status, ['draft', 'returned'], true), 403);

        $ctrl = app($meta['controller']);

        try {
            $ctrl->applyUpdate($request, $model);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }

        return redirect()->route('online.show', ['type' => $type, 'id' => $model->id])
            ->with('success', 'Application updated successfully.');
    }

    public function submit(Request $request, string $type, int $id)
    {
        $this->typeMeta($type) ?? abort(404);
        $model = $this->resolveModel($type, $id);

        if (! in_array($model->status, ['draft', 'returned'], true)) {
            return back()->with('error', 'Only draft or returned applications can be submitted.');
        }

        if ($model->applicationRequirements()->count() === 0) {
            return back()->with('error', 'Please upload at least one required document before submitting this application for review.');
        }

        $model->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
            'review_remarks' => null,
        ]);

        activity()->causedBy(Auth::user())->performedOn($model)
            ->log('Application submitted online — awaiting Engineering approval');

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($model));

        return redirect()->route('online.show', ['type' => $type, 'id' => $model->id])
            ->with('success', 'Application submitted. Engineering will review it shortly.');
    }

    public function show(string $type, int $id)
    {
        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);
        $model = $this->resolveModel($type, $id);

        $model->load(array_merge($meta['show_relations'], ['applicationRequirements']));

        return view($meta['show_view'], [
            'application' => $model,
            'applicationType' => strtoupper($type),
            'portal' => 'client',
        ]);
    }

    public function uploadRequirements(string $type, int $id)
    {
        $this->typeMeta($type) ?? abort(404);
        $model = $this->resolveModel($type, $id);

        $application = $model;
        $applicationType = $type;
        $requirements = $model->applicationRequirements;

        return view('online.upload', compact('application', 'applicationType', 'requirements'));
    }

    public function storeRequirement(Request $request, string $type, int $id)
    {
        $this->typeMeta($type) ?? abort(404);
        $model = $this->resolveModel($type, $id);

        $request->validate([
            'requirement_name' => 'required|string|max:255',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $path = $request->file('file')->store('requirements/' . strtolower($type) . '-' . $model->id, 'public');

        $model->applicationRequirements()->create([
            'requirement_name' => $request->requirement_name,
            'file_path' => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Requirement uploaded.');
    }

    public function track(string $type, int $id)
    {
        $this->typeMeta($type) ?? abort(404);
        $model = $this->resolveModel($type, $id);
        $model->load('permits', 'collections');

        $timeline = ApplicationTimeline::build($model, $type);

        $application = $model;
        $applicationType = $type;

        return view('online.track', compact('application', 'applicationType', 'timeline'));
    }

    public function downloadPermit(string $type, int $id)
    {
        $this->typeMeta($type) ?? abort(404);
        $model = $this->resolveModel($type, $id);

        $permit = $model->permits()->latest()->first();

        if (! $permit) {
            return back()->with('error', 'Permit not yet generated.');
        }

        $model->load('applicationRequirements', 'collections.collectionDetails');
        if ($type === 'BP') {
            $model->load('applicantBarangay', 'buildingBarangay', 'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup', 'permitType', 'scopeOfWork');
        }

        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');
        $template = $type === 'OP' ? 'pdf.occupancy-permit' : 'pdf.building-permit';

        $settings = Setting::general();
        $sealImage = Setting::imageDataUri($settings, 'general.logo');
        $dpwhLogo = Setting::imageDataUri($settings, 'general.dpwh_logo');
        if (! $dpwhLogo) {
            $dpwhLogoPath = public_path('images/dpwh-logo.png');
            if (file_exists($dpwhLogoPath)) {
                $dpwhLogo = 'data:image/png;base64,' . base64_encode(file_get_contents($dpwhLogoPath));
            }
        }

        $verifyPath = route('verify.permit', $permit->verification_token, absolute: false);
        $domain = ! empty($settings['general.domain']) ? rtrim($settings['general.domain'], '/') : rtrim(config('app.url'), '/');
        $qrCode = new \Endroid\QrCode\QrCode(
            data: $domain . $verifyPath,
            size: 300,
            margin: 4,
        );
        $qrImage = (new \Endroid\QrCode\Writer\PngWriter())->write($qrCode)->getDataUri();

        $application = $model;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, compact('permit', 'application', 'signatories', 'settings', 'sealImage', 'dpwhLogo', 'qrImage'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("permit_{$permit->permit_number}.pdf");
    }

    /**
     * Print the filled-in application form itself. Available at any status
     * (including draft) so clients can print/preview what they've filled in.
     */
    public function printForm(string $type, int $id)
    {
        $meta = $this->typeMeta($type);
        abort_unless($meta, 404);

        $model = $this->resolveModel($type, $id);

        $ctrl = app($meta['controller']);
        abort_unless(method_exists($ctrl, 'printForm'), 404);

        return $ctrl->printForm($model);
    }

    /**
     * Print one of the 6 BP discipline-specific forms (Architectural, Structural,
     * Electrical, Sanitary, Mechanical, Electronics). Discipline forms only exist
     * for Building Permit applications.
     */
    public function printDiscipline(string $type, int $id, string $discipline)
    {
        abort_unless($type === 'BP', 404);

        $model = $this->resolveModel($type, $id);

        return app(ApplicationController::class)->printDiscipline($model, $discipline);
    }

    private function portalViewVars(array $data, string $type, string $formAction): array
    {
        $data['portal'] = 'client';
        $data['formAction'] = $formAction;
        $data['indexUrl'] = route('online.dashboard');
        $data['indexLabel'] = 'My Applications';
        $data['homeUrl'] = route('online.dashboard');
        $data['homeLabel'] = 'My Applications';

        return $data;
    }
}
