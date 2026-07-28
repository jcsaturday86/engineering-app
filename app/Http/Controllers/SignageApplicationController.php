<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use App\Models\PermitType;
use App\Models\Province;
use App\Models\SignageApplication;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class SignageApplicationController extends Controller
{
    private function filteredQuery(Request $request): array
    {
        $query = SignageApplication::where('status', '!=', 'cancelled');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                    ->orWhere('applicant_first_name', 'like', "%{$search}%")
                    ->orWhere('applicant_last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfYear()->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : now()->toDateString();
        $query->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

        return [$query, $dateFrom, $dateTo];
    }

    public function index(Request $request)
    {
        [$query, $dateFrom, $dateTo] = $this->filteredQuery($request);

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('signage-applications.index', compact('applications', 'dateFrom', 'dateTo'));
    }

    public function report(Request $request)
    {
        [$query, $dateFrom, $dateTo] = $this->filteredQuery($request);

        $data = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'data' => $data,
            'reportType' => 'signage',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("signage_applications_report_{$dateFrom}_{$dateTo}.pdf");
    }

    public function create()
    {
        $sgpPermitType = PermitType::where('code', 'SGP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = null;

        return view('signage-applications.form', $data);
    }

    public function store(Request $request)
    {
        try {
            $application = $this->persistApplication($request, [
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }

        return redirect()->route('signage-applications.show', $application)
            ->with('success', "Application {$application->application_number} created successfully.");
    }

    /**
     * Validate and create a SignageApplication. Shared by the staff store()
     * and the client online-portal submission.
     */
    public function persistApplication(Request $request, array $overrides = []): SignageApplication
    {
        $validated = $this->validateApplication($request);

        return DB::transaction(function () use ($validated, $overrides) {
            $counter = DB::table('signage_applications')
                ->where('app_year', now()->year)
                ->where('app_month', now()->month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $appNumber = sprintf('SGP-%s-%s-%05d', now()->format('Y'), now()->format('m'), $nextCounter);

            return SignageApplication::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $nextCounter,
                'application_number' => $appNumber,
                'status' => $overrides['status'] ?? 'draft',
                'source' => $overrides['source'] ?? 'walk_in',
                'entered_by' => $overrides['entered_by'] ?? Auth::id(),
                'client_user_id' => $overrides['client_user_id'] ?? null,
            ]));
        });
    }

    public function show(SignageApplication $signageApplication)
    {
        $signageApplication->load([
            'applicantProvince', 'applicantCity', 'applicantBarangay',
            'assessments.assessmentItems', 'billings', 'collections', 'permits',
        ]);

        $application = $signageApplication;

        return view('signage-applications.show', compact('application'));
    }

    public function edit(SignageApplication $signageApplication)
    {
        $sgpPermitType = PermitType::where('code', 'SGP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = $signageApplication;

        return view('signage-applications.form', $data);
    }

    public function update(Request $request, SignageApplication $signageApplication)
    {
        try {
            $this->applyUpdate($request, $signageApplication);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }

        return redirect()->route('signage-applications.show', $signageApplication)
            ->with('success', 'Application updated successfully.');
    }

    /**
     * Validate and persist edits to an existing SignageApplication. Shared
     * by the staff update() and the client online-portal edit/resubmit flow.
     */
    public function applyUpdate(Request $request, SignageApplication $signageApplication): void
    {
        $validated = $this->validateApplication($request);

        DB::transaction(function () use ($signageApplication, $validated) {
            $signageApplication->update($validated);
        });
    }

    public function submit(Request $request, SignageApplication $signageApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($signageApplication->status !== 'draft') {
            return back()->with('error', 'Only draft applications can be submitted.');
        }

        $signageApplication->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        activity()->causedBy(Auth::user())->performedOn($signageApplication)
            ->log('Signage application submitted — routed to Engineering Assessment');

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($signageApplication));

        return back()->with('success', 'Application submitted. Routed to Engineering Assessment.');
    }

    public function revertSubmission(Request $request, SignageApplication $signageApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($signageApplication->status !== 'submitted') {
            return back()->with('error', 'Only submitted applications can have their submission reverted.');
        }

        if ($signageApplication->assessments()->where('status', 'finalized')->exists()) {
            return back()->with('error', 'Cannot revert: engineering assessment has already started.');
        }

        DB::transaction(function () use ($signageApplication) {
            $signageApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($signageApplication)->log('Signage application submission reverted to draft');

        return back()->with('success', 'Application submission reverted to draft.');
    }

    public function cancel(Request $request, SignageApplication $signageApplication)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (in_array($signageApplication->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Cannot cancel an application that has been paid or has a permit generated.');
        }

        $signageApplication->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        activity()->causedBy(Auth::user())->performedOn($signageApplication)->log('Application cancelled');

        return redirect()->route('signage-applications.index')->with('warning', 'Application has been cancelled.');
    }

    public function printForm(SignageApplication $signageApplication)
    {
        $signageApplication->load(['applicantProvince', 'applicantCity', 'applicantBarangay']);

        $application = $signageApplication;

        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');
        $sealImage = \App\Models\Setting::imageDataUri($settings, 'general.logo');
        $nationalGovtLogo = \App\Models\Setting::imageDataUri($settings, 'general.national_govt_logo');
        [$boTitle, $boName, $boDesignation] = $this->resolveBuildingOfficial($application);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.signage-application-form', compact('application', 'sealImage', 'nationalGovtLogo', 'settings', 'boTitle', 'boName', 'boDesignation'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("sgp_application_{$application->application_number}.pdf");
    }

    /**
     * Prefer the generated Permit's immutable building-official snapshot; fall back to the
     * currently-active Building Official signatory when no Permit has been generated yet.
     *
     * @return array{0: string, 1: string, 2: string} [title, name, designation]
     */
    private function resolveBuildingOfficial(SignageApplication $application): array
    {
        $permit = $application->permits->first();

        if ($permit) {
            return [
                $permit->building_official_title ?? '',
                $permit->building_official_name ?? '',
                $permit->building_official_designation ?? 'Building Official',
            ];
        }

        $signatory = \App\Models\Signatory::where('role', 'building_official')->where('is_active', true)->first();

        return [
            $signatory?->title ?? '',
            $signatory?->name ?? '',
            $signatory?->designation ?? 'Building Official',
        ];
    }

    public function getFormData(): array
    {
        $sfcCityId = City::where('name', 'like', '%SAN FERNANDO%')->where('province_id', 3)->value('id') ?? 71;

        return [
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'cities' => City::where('is_active', true)->orderBy('name')->get(),
            'sfcBarangays' => Barangay::where('city_id', $sfcCityId)->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function validateApplication(Request $request): array
    {
        $validated = $request->validate([
            // Applicant
            'applicant_first_name' => 'required|string|max:255',
            'applicant_middle_name' => 'nullable|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            // Address
            'applicant_province_id' => 'required|exists:provinces,id',
            'applicant_city_id' => 'required|exists:cities,id',
            'applicant_barangay_id' => 'required|exists:barangays,id',
            'applicant_street' => 'nullable|string|max:255',
            'applicant_zip_code' => 'nullable|string|max:10',
            // Scope of Work
            'install' => 'nullable|boolean',
            'install_detail' => 'nullable|string|max:500',
            'attach' => 'nullable|boolean',
            'attach_detail' => 'nullable|string|max:500',
            'paint' => 'nullable|boolean',
            'paint_detail' => 'nullable|string|max:500',
            'wordings' => 'nullable|string|max:500',
            'premises_of' => 'nullable|string|max:255',
        ]);

        $validated['install'] = $request->boolean('install');
        $validated['attach'] = $request->boolean('attach');
        $validated['paint'] = $request->boolean('paint');

        if (! $validated['install'] && ! $validated['attach'] && ! $validated['paint']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'install' => 'Select at least one Scope of Work item (Install, Attach, or Paint).',
            ]);
        }

        return $validated;
    }
}
