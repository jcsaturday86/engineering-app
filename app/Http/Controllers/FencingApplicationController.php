<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use App\Models\FencingApplication;
use App\Models\FormOfOwnership;
use App\Models\PermitType;
use App\Models\Province;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class FencingApplicationController extends Controller
{
    private function filteredQuery(Request $request): array
    {
        $query = FencingApplication::where('status', '!=', 'cancelled');

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

        return view('fencing-applications.index', compact('applications', 'dateFrom', 'dateTo'));
    }

    public function report(Request $request)
    {
        [$query, $dateFrom, $dateTo] = $this->filteredQuery($request);

        $data = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'data' => $data,
            'reportType' => 'fencing',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("fencing_applications_report_{$dateFrom}_{$dateTo}.pdf");
    }

    public function create()
    {
        $fpPermitType = PermitType::where('code', 'FP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = null;

        return view('fencing-applications.form', $data);
    }

    public function store(Request $request)
    {
        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $counter = DB::table('fencing_applications')
                ->where('app_year', now()->year)
                ->where('app_month', now()->month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $appNumber = sprintf('FP-%s-%s-%05d', now()->format('Y'), now()->format('m'), $nextCounter);

            $application = FencingApplication::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $nextCounter,
                'application_number' => $appNumber,
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
            ]));

            DB::commit();

            return redirect()->route('fencing-applications.show', $application)
                ->with('success', "Application {$appNumber} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }
    }

    public function show(FencingApplication $fencingApplication)
    {
        $fencingApplication->load([
            'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'constructionBarangay',
            'assessments.assessmentItems', 'billings', 'collections', 'permits',
        ]);

        $application = $fencingApplication;

        return view('fencing-applications.show', compact('application'));
    }

    public function edit(FencingApplication $fencingApplication)
    {
        $fpPermitType = PermitType::where('code', 'FP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = $fencingApplication;

        return view('fencing-applications.form', $data);
    }

    public function update(Request $request, FencingApplication $fencingApplication)
    {
        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $fencingApplication->update($validated);

            DB::commit();

            return redirect()->route('fencing-applications.show', $fencingApplication)
                ->with('success', 'Application updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, FencingApplication $fencingApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($fencingApplication->status !== 'draft') {
            return back()->with('error', 'Only draft applications can be submitted.');
        }

        $fencingApplication->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        activity()->causedBy(Auth::user())->performedOn($fencingApplication)
            ->log('Fencing application submitted — routed to Engineering Assessment');

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($fencingApplication));

        return back()->with('success', 'Application submitted. Routed to Engineering Assessment.');
    }

    public function revertSubmission(Request $request, FencingApplication $fencingApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($fencingApplication->status !== 'submitted') {
            return back()->with('error', 'Only submitted applications can have their submission reverted.');
        }

        if ($fencingApplication->assessments()->where('status', 'finalized')->exists()) {
            return back()->with('error', 'Cannot revert: engineering assessment has already started.');
        }

        DB::transaction(function () use ($fencingApplication) {
            $fencingApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($fencingApplication)->log('Fencing application submission reverted to draft');

        return back()->with('success', 'Application submission reverted to draft.');
    }

    public function cancel(Request $request, FencingApplication $fencingApplication)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (in_array($fencingApplication->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Cannot cancel an application that has been paid or has a permit generated.');
        }

        $fencingApplication->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        activity()->causedBy(Auth::user())->performedOn($fencingApplication)->log('Application cancelled');

        return redirect()->route('fencing-applications.index')->with('warning', 'Application has been cancelled.');
    }

    public function printForm(FencingApplication $fencingApplication)
    {
        $fencingApplication->load([
            'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'constructionBarangay',
            'permits',
        ]);

        $application = $fencingApplication;

        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');
        $sealImage = \App\Models\Setting::imageDataUri($settings, 'general.logo');
        $nationalGovtLogo = \App\Models\Setting::imageDataUri($settings, 'general.national_govt_logo');
        [$boTitle, $boName, $boDesignation] = $this->resolveBuildingOfficial($application);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.fencing-application-form', compact('application', 'sealImage', 'nationalGovtLogo', 'settings', 'boTitle', 'boName', 'boDesignation'));
        $pdf->setOption('defaultMediaType', 'print');
        $pdf->setOption('dpi', 200);
        $pdf->setPaper([0, 0, 595.44, 840.96]);

        return $pdf->stream("fp_application_{$application->application_number}.pdf");
    }

    /**
     * Prefer the generated Permit's immutable building-official snapshot; fall back to the
     * currently-active Building Official signatory when no Permit has been generated yet.
     *
     * @return array{0: string, 1: string, 2: string} [title, name, designation]
     */
    private function resolveBuildingOfficial(FencingApplication $application): array
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

    private function getFormData(): array
    {
        $sfcCityId = City::where('name', 'like', '%SAN FERNANDO%')->where('province_id', 3)->value('id') ?? 71;

        return [
            'formOfOwnerships' => FormOfOwnership::where('is_active', true)->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'cities' => City::where('is_active', true)->orderBy('name')->get(),
            'sfcBarangays' => Barangay::where('city_id', $sfcCityId)->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateApplication(Request $request): array
    {
        $validated = $request->validate([
            // Applicant
            'applicant_first_name' => 'required|string|max:255',
            'applicant_middle_name' => 'nullable|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_tin' => 'nullable|string|max:50',
            'applicant_telephone' => 'nullable|string|max:20',
            // Enterprise
            'owned_by_enterprise' => 'nullable|boolean',
            'enterprise_name' => 'nullable|string|max:255',
            'form_of_ownership_id' => 'nullable|exists:form_of_ownerships,id',
            // Address
            'applicant_province_id' => 'required|exists:provinces,id',
            'applicant_city_id' => 'required|exists:cities,id',
            'applicant_barangay_id' => 'required|exists:barangays,id',
            'applicant_street' => 'nullable|string|max:255',
            'applicant_zip_code' => 'nullable|string|max:10',
            'applicant_ctc_no' => 'nullable|string|max:50',
            'applicant_ctc_date_issued' => 'nullable|date',
            'applicant_ctc_issued_at' => 'nullable|string|max:255',
            // Location of Construction
            'lot_no' => 'nullable|string|max:50',
            'block_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:100',
            'tax_dec_no' => 'nullable|string|max:100',
            'construction_street' => 'required|string|max:255',
            'construction_barangay_id' => 'required|exists:barangays,id',
            // Scope of Work
            'scope_of_work' => 'required|in:new_construction,erection,addition,repair,others',
            'scope_of_work_detail' => 'nullable|string|max:500',
            // Design Professional, Plans and Specifications
            'design_professional_name' => 'nullable|string|max:255',
            'design_professional_address' => 'nullable|string|max:255',
            'design_professional_prc_no' => 'nullable|string|max:50',
            'design_professional_prc_validity' => 'nullable|date',
            'design_professional_ptr_no' => 'nullable|string|max:50',
            'design_professional_ptr_date_issued' => 'nullable|date',
            'design_professional_ptr_issued_at' => 'nullable|string|max:255',
            'design_professional_tin' => 'nullable|string|max:50',
            // Full-Time Inspector or Supervisor
            'inspector_name' => 'nullable|string|max:255',
            'inspector_address' => 'nullable|string|max:255',
            'inspector_prc_no' => 'nullable|string|max:50',
            'inspector_prc_validity' => 'nullable|date',
            'inspector_ptr_no' => 'nullable|string|max:50',
            'inspector_ptr_date_issued' => 'nullable|date',
            'inspector_ptr_issued_at' => 'nullable|string|max:255',
            'inspector_tin' => 'nullable|string|max:50',
            // Consent of Lot Owner
            'owner_name' => 'nullable|string|max:255',
            'owner_address' => 'nullable|string|max:255',
            'owner_ctc_no' => 'nullable|string|max:50',
            'owner_ctc_date_issued' => 'nullable|date',
            'owner_ctc_issued_at' => 'nullable|string|max:255',
            // Misc
            'remarks' => 'nullable|string|max:1000',
        ]);

        $validated['owned_by_enterprise'] = $request->boolean('owned_by_enterprise');

        return $validated;
    }
}
