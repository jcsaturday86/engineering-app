<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use App\Models\DemolitionApplication;
use App\Models\FormOfOwnership;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use App\Models\PermitType;
use App\Models\Province;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class DemolitionApplicationController extends Controller
{
    private function filteredQuery(Request $request): array
    {
        $query = DemolitionApplication::where('status', '!=', 'cancelled');

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

        return view('demolition-applications.index', compact('applications', 'dateFrom', 'dateTo'));
    }

    public function report(Request $request)
    {
        [$query, $dateFrom, $dateTo] = $this->filteredQuery($request);

        $data = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'data' => $data,
            'reportType' => 'demolition',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("demolition_applications_report_{$dateFrom}_{$dateTo}.pdf");
    }

    public function create()
    {
        $dpPermitType = PermitType::where('code', 'DP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = null;

        return view('demolition-applications.form', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'occupancy_sub_groups' => 'required|array|min:1',
        ], [
            'occupancy_sub_groups.required' => 'Please select at least one Character of Occupancy.',
            'occupancy_sub_groups.min' => 'Please select at least one Character of Occupancy.',
        ]);

        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $counter = DB::table('demolition_applications')
                ->where('app_year', now()->year)
                ->where('app_month', now()->month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $appNumber = sprintf('DP-%s-%s-%05d', now()->format('Y'), now()->format('m'), $nextCounter);

            $application = DemolitionApplication::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $nextCounter,
                'application_number' => $appNumber,
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
            ]));

            $this->saveOccupancyGroups($application, $request);

            DB::commit();

            return redirect()->route('demolition-applications.show', $application)
                ->with('success', "Application {$appNumber} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }
    }

    public function show(DemolitionApplication $demolitionApplication)
    {
        $demolitionApplication->load([
            'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'demolitionBarangay',
            'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup',
            'assessments.assessmentItems', 'billings', 'collections', 'permits',
        ]);

        $application = $demolitionApplication;

        return view('demolition-applications.show', compact('application'));
    }

    public function edit(DemolitionApplication $demolitionApplication)
    {
        $dpPermitType = PermitType::where('code', 'DP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = $demolitionApplication->load('applicationOccupancyGroups');

        return view('demolition-applications.form', $data);
    }

    public function update(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate([
            'occupancy_sub_groups' => 'required|array|min:1',
        ], [
            'occupancy_sub_groups.required' => 'Please select at least one Character of Occupancy.',
            'occupancy_sub_groups.min' => 'Please select at least one Character of Occupancy.',
        ]);

        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $demolitionApplication->update($validated);

            $demolitionApplication->applicationOccupancyGroups()->delete();
            $this->saveOccupancyGroups($demolitionApplication, $request);

            DB::commit();

            return redirect()->route('demolition-applications.show', $demolitionApplication)
                ->with('success', 'Application updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($demolitionApplication->status !== 'draft') {
            return back()->with('error', 'Only draft applications can be submitted.');
        }

        $demolitionApplication->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        activity()->causedBy(Auth::user())->performedOn($demolitionApplication)
            ->log('Demolition application submitted — routed to Engineering Assessment');

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($demolitionApplication));

        return back()->with('success', 'Application submitted. Routed to Engineering Assessment.');
    }

    public function revertSubmission(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($demolitionApplication->status !== 'submitted') {
            return back()->with('error', 'Only submitted applications can have their submission reverted.');
        }

        if ($demolitionApplication->assessments()->where('status', 'finalized')->exists()) {
            return back()->with('error', 'Cannot revert: engineering assessment has already started.');
        }

        DB::transaction(function () use ($demolitionApplication) {
            $demolitionApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($demolitionApplication)->log('Demolition application submission reverted to draft');

        return back()->with('success', 'Application submission reverted to draft.');
    }

    public function cancel(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (in_array($demolitionApplication->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Cannot cancel an application that has been paid or has a permit generated.');
        }

        $demolitionApplication->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        activity()->causedBy(Auth::user())->performedOn($demolitionApplication)->log('Application cancelled');

        return redirect()->route('demolition-applications.index')->with('warning', 'Application has been cancelled.');
    }

    public function printForm(DemolitionApplication $demolitionApplication)
    {
        $demolitionApplication->load([
            'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'demolitionBarangay',
            'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup',
        ]);

        $application = $demolitionApplication;

        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');
        $sealImage = \App\Models\Setting::imageDataUri($settings, 'general.logo');
        $nationalGovtLogo = \App\Models\Setting::imageDataUri($settings, 'general.national_govt_logo');
        [$boTitle, $boName, $boDesignation] = $this->resolveBuildingOfficial($application);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.demolition-application-form', compact('application', 'sealImage', 'nationalGovtLogo', 'settings', 'boTitle', 'boName', 'boDesignation'));
        $pdf->setOption('defaultMediaType', 'print');
        $pdf->setOption('dpi', 200);
        $pdf->setPaper([0, 0, 612, 936]);

        return $pdf->stream("dp_application_{$application->application_number}.pdf");
    }

    /**
     * Prefer the generated Permit's immutable building-official snapshot; fall back to the
     * currently-active Building Official signatory when no Permit has been generated yet.
     *
     * @return array{0: string, 1: string, 2: string} [title, name, designation]
     */
    private function resolveBuildingOfficial(DemolitionApplication $application): array
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
            'occupancyGroups' => OccupancyGroup::with('subGroups')->where('is_active', true)->orderBy('sort_order')->get(),
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
            'applicant_ctc_place_issued' => 'nullable|string|max:255',
            // Location of Demolition Works
            'lot_no' => 'nullable|string|max:50',
            'block_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:100',
            'tax_dec_no' => 'nullable|string|max:100',
            'demolition_street' => 'required|string|max:255',
            'demolition_barangay_id' => 'required|exists:barangays,id',
            // Scope of Work
            'scope_of_work' => 'required|in:demolition,others',
            'scope_of_work_detail' => 'nullable|string|max:500',
            // Full-time Inspector
            'inspector_name' => 'required|string|max:255',
            'inspector_address' => 'required|string|max:255',
            'inspector_telephone' => 'nullable|string|max:20',
            'inspector_prc_no' => 'required|string|max:50',
            'inspector_prc_validity' => 'required|date',
            'inspector_ptr_no' => 'required|string|max:50',
            'inspector_ptr_date_issued' => 'required|date',
            'inspector_ptr_issued_at' => 'required|string|max:255',
            'inspector_tin' => 'required|string|max:50',
            // Lot Owner Consent
            'owner_name' => 'nullable|string|max:255',
            'owner_ctc_no' => 'nullable|string|max:50',
            'owner_ctc_date_issued' => 'nullable|date',
            'owner_ctc_place_issued' => 'nullable|string|max:255',
            // Misc
            'remarks' => 'nullable|string|max:1000',
        ]);

        $validated['owned_by_enterprise'] = $request->boolean('owned_by_enterprise');

        return $validated;
    }

    private function saveOccupancyGroups(DemolitionApplication $application, Request $request): void
    {
        $selectedIds = $request->input('occupancy_sub_groups', []);

        if (empty($selectedIds)) {
            return;
        }

        $subGroups = OccupancySubGroup::with('occupancyGroup')
            ->whereIn('id', $selectedIds)
            ->get();

        foreach ($subGroups as $subGroup) {
            $application->applicationOccupancyGroups()->create([
                'application_id' => null,
                'occupancy_group_id' => $subGroup->occupancy_group_id,
                'occupancy_sub_group_id' => $subGroup->id,
                'others_text' => $request->input("sub_group_{$subGroup->id}_others"),
            ]);
        }
    }
}
