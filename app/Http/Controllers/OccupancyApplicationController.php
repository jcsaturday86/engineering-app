<?php

namespace App\Http\Controllers;

use App\Models\ApplicationType;
use App\Models\Barangay;
use App\Models\City;
use App\Models\FormOfOwnership;
use App\Models\LandClassification;
use App\Models\OccupancyApplication;
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

class OccupancyApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = OccupancyApplication::with('applicationType', 'permits')
            ->where('status', '!=', 'cancelled');

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

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('occupancy-applications.index', compact('applications', 'year'));
    }

    public function create()
    {
        $opPermitType = PermitType::where('code', 'OP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData($opPermitType->id);
        $data['application'] = null;

        return view('occupancy-applications.form', $data);
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
            $counter = OccupancyApplication::where('app_year', now()->year)
                    ->where('app_month', now()->month)
                    ->count() + 1;

            $appNumber = sprintf('OP-%s-%s-%05d', now()->format('Y'), now()->format('m'), $counter);

            $application = OccupancyApplication::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $counter,
                'application_number' => $appNumber,
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
            ]));

            $this->saveOccupancyGroups($application, $request);

            DB::commit();

            return redirect()->route('occupancy-applications.show', $application)
                ->with('success', "Application {$appNumber} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }
    }

    public function show(OccupancyApplication $occupancyApplication)
    {
        $occupancyApplication->load([
            'applicationType', 'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'buildingBarangay',
            'landClassification', 'applicationOccupancyGroups.occupancyGroup',
            'applicationOccupancyGroups.occupancySubGroup',
            'assessments.assessmentItems', 'billings', 'collections', 'permits',
        ]);

        $application = $occupancyApplication;

        return view('occupancy-applications.show', compact('application'));
    }

    public function edit(OccupancyApplication $occupancyApplication)
    {
        $opPermitType = PermitType::where('code', 'OP')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData($opPermitType->id);
        $data['application'] = $occupancyApplication->load('applicationOccupancyGroups');

        return view('occupancy-applications.form', $data);
    }

    public function update(Request $request, OccupancyApplication $occupancyApplication)
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
            $occupancyApplication->update($validated);

            $occupancyApplication->applicationOccupancyGroups()->delete();
            $this->saveOccupancyGroups($occupancyApplication, $request);

            DB::commit();

            return redirect()->route('occupancy-applications.show', $occupancyApplication)
                ->with('success', 'Application updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, OccupancyApplication $occupancyApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($occupancyApplication->status !== 'draft') {
            return back()->with('error', 'Only draft applications can be submitted.');
        }

        // OP skips zoning — goes directly to zoning_assessed for engineering pickup
        $occupancyApplication->update([
            'status' => 'zoning_assessed',
            'submitted_at' => now(),
        ]);

        activity()->causedBy(Auth::user())->performedOn($occupancyApplication)
            ->log('Occupancy application submitted — routed to Engineering Assessment');

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($occupancyApplication));

        return back()->with('success', 'Application submitted. Routed directly to Engineering Assessment.');
    }

    public function revertSubmission(Request $request, OccupancyApplication $occupancyApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($occupancyApplication->status !== 'zoning_assessed') {
            return back()->with('error', 'Only submitted applications can have their submission reverted.');
        }

        if ($occupancyApplication->assessments()->where('status', 'finalized')->exists()) {
            return back()->with('error', 'Cannot revert: engineering assessment has already started.');
        }

        DB::transaction(function () use ($occupancyApplication) {
            $occupancyApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($occupancyApplication)->log('Occupancy application submission reverted to draft');

        return back()->with('success', 'Application submission reverted to draft.');
    }

    public function cancel(Request $request, OccupancyApplication $occupancyApplication)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (in_array($occupancyApplication->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Cannot cancel an application that has been paid or has a permit generated.');
        }

        $occupancyApplication->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        activity()->causedBy(Auth::user())->performedOn($occupancyApplication)->log('Application cancelled');

        return redirect()->route('occupancy-applications.index')->with('warning', 'Application has been cancelled.');
    }

    public function printForm(OccupancyApplication $occupancyApplication)
    {
        $occupancyApplication->load([
            'applicationType', 'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'buildingBarangay',
            'landClassification', 'applicationOccupancyGroups.occupancyGroup',
            'applicationOccupancyGroups.occupancySubGroup',
        ]);

        $application = $occupancyApplication;

        $signatories = \App\Models\Signatory::where('is_active', true)->get()->keyBy('role');

        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');
        $sealImage = null;
        if (! empty($settings['general.logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['general.logo'])) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($settings['general.logo']);
            $sealImage = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($settings['general.logo']));
        }

        return view('pdf.application-form', compact('application', 'signatories', 'sealImage'));
    }

    private function getFormData(int $permitTypeId): array
    {
        $sfcCityId = City::where('name', 'like', '%SAN FERNANDO%')->where('province_id', 3)->value('id') ?? 71;

        return [
            'applicationTypes' => ApplicationType::where('is_active', true)
                ->where('permit_type_id', $permitTypeId)
                ->orderBy('sort_order')->get(),
            'formOfOwnerships' => FormOfOwnership::where('is_active', true)->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'cities' => City::where('is_active', true)->orderBy('name')->get(),
            'sfcBarangays' => Barangay::where('city_id', $sfcCityId)->where('is_active', true)->orderBy('name')->get(),
            'occupancyGroups' => OccupancyGroup::with('subGroups')->where('is_active', true)->orderBy('sort_order')->get(),
            'landClassifications' => LandClassification::where('is_active', true)->get(),
        ];
    }

    private function validateApplication(Request $request): array
    {
        return $request->validate([
            'application_type_id' => 'required|exists:application_types,id',
            // Applicant
            'applicant_first_name' => 'required|string|max:255',
            'applicant_middle_name' => 'nullable|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_suffix' => 'nullable|string|max:20',
            'applicant_tin' => 'nullable|string|max:50',
            'applicant_contact_no' => 'required|string|max:20',
            'applicant_email' => 'nullable|email|max:255',
            'applicant_govt_id' => 'nullable|string|max:100',
            'applicant_id_date_issued' => 'nullable|date',
            'applicant_id_place_issued' => 'nullable|string|max:255',
            'applicant_date_signed' => 'nullable|date',
            // Enterprise
            'enterprise_name' => 'nullable|string|max:255',
            'form_of_ownership_id' => 'nullable|exists:form_of_ownerships,id',
            // Address
            'applicant_province_id' => 'required|exists:provinces,id',
            'applicant_city_id' => 'required|exists:cities,id',
            'applicant_barangay_id' => 'required|exists:barangays,id',
            'applicant_street' => 'nullable|string|max:255',
            'applicant_zip_code' => 'nullable|string|max:10',
            // Project
            'project_title' => 'required|string|max:255',
            // Building Location
            'lot_no' => 'nullable|string|max:50',
            'block_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:100',
            'tax_dec_no' => 'nullable|string|max:100',
            'land_classification_id' => 'nullable|exists:land_classifications,id',
            'building_street' => 'required|string|max:255',
            'building_barangay_id' => 'required|exists:barangays,id',
            // Building Specs
            'no_of_storeys' => 'required|integer|min:1',
            'no_of_units' => 'required|integer|min:1',
            'occupancy_classified' => 'nullable|string|max:255',
            'total_floor_area' => 'required|numeric|min:0',
            'lot_area' => 'nullable|numeric|min:0',
            // Owner
            'owner_name' => 'nullable|string|max:255',
            'owner_address' => 'nullable|string|max:255',
            'owner_govt_id' => 'nullable|string|max:100',
            'owner_id_date_issued' => 'nullable|date',
            'owner_id_place_issued' => 'nullable|string|max:255',
            'owner_date_signed' => 'nullable|date',
            // OP-specific
            'bp_number' => 'nullable|string|max:30',
            'bp_issued_date' => 'nullable|date',
            'fsec_no' => 'nullable|string|max:50',
            'fsec_issued_date' => 'nullable|date',
            'fsic_no' => 'nullable|string|max:50',
            'applies_for' => 'nullable|in:full,partial',
            'completion_date' => 'required|date',
            // Misc
            'remarks' => 'nullable|string|max:1000',
        ]);
    }

    private function saveOccupancyGroups(OccupancyApplication $application, Request $request): void
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
