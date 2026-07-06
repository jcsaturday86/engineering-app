<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationType;
use App\Models\Barangay;
use App\Models\City;
use App\Models\FormOfOwnership;
use App\Models\LandClassification;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use App\Models\PermitType;
use App\Models\Province;
use App\Models\ScopeOfWork;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with('permitType', 'applicationType')
            ->where('status', '!=', 'cancelled');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                    ->orWhere('applicant_first_name', 'like', "%{$search}%")
                    ->orWhere('applicant_last_name', 'like', "%{$search}%")
                    ->orWhere('project_title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('applications.index', compact('applications'));
    }

    public function create()
    {
        $permitType = PermitType::where('code', 'BP')->where('is_active', true)->firstOrFail();

        $data = $this->getFormData($permitType->id);
        $data['permitType'] = $permitType;
        $data['application'] = null;

        return view('applications.form', $data);
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

        if (!empty($validated['scope_of_work_id'])) {
            $validated['scope_of_work_details'] = $request->input('scope_detail_' . $validated['scope_of_work_id'], '');
        }

        $validated['applies_to'] = $request->boolean('skip_locational') ? 'SKIP_LC' : '';

        $permitType = PermitType::where('code', 'BP')->firstOrFail();

        DB::beginTransaction();
        try {
            $counter = Application::where('permit_type_id', $permitType->id)
                    ->where('app_year', now()->year)
                    ->where('app_month', now()->month)
                    ->count() + 1;

            $appNumber = sprintf(
                'BP-%s-%s-%05d',
                now()->format('Y'),
                now()->format('m'),
                $counter
            );

            $application = Application::create(array_merge($validated, [
                'permit_type_id' => $permitType->id,
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $counter,
                'application_number' => $appNumber,
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
                'total_estimated_cost' => ($validated['building_cost'] ?? 0) +
                    ($validated['electrical_cost'] ?? 0) +
                    ($validated['mechanical_cost'] ?? 0) +
                    ($validated['electronics_cost'] ?? 0) +
                    ($validated['plumbing_cost'] ?? 0) +
                    ($validated['other_equipment_cost'] ?? 0) +
                    ($validated['equipment_cost_1'] ?? 0) +
                    ($validated['equipment_cost_2'] ?? 0) +
                    ($validated['equipment_cost_3'] ?? 0) +
                    ($validated['equipment_cost_4'] ?? 0),
            ]));

            $this->saveOccupancyGroups($application, $request);

            DB::commit();

            return redirect()->route('applications.show', $application)
                ->with('success', "Application {$appNumber} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }
    }

    public function show(Application $application)
    {
        $application->load([
            'permitType', 'applicationType', 'scopeOfWork', 'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'buildingBarangay',
            'landClassification', 'applicationOccupancyGroups.occupancyGroup',
            'applicationOccupancyGroups.occupancySubGroup',
            'assessments.assessmentItems', 'billings', 'collections', 'permits',
        ]);

        return view('applications.show', compact('application'));
    }

    public function edit(Application $application)
    {
        $data = $this->getFormData($application->permit_type_id);
        $data['permitType'] = $application->permitType;
        $data['application'] = $application->load('applicationOccupancyGroups');

        return view('applications.form', $data);
    }

    public function update(Request $request, Application $application)
    {
        $request->validate([
            'occupancy_sub_groups' => 'required|array|min:1',
        ], [
            'occupancy_sub_groups.required' => 'Please select at least one Character of Occupancy.',
            'occupancy_sub_groups.min' => 'Please select at least one Character of Occupancy.',
        ]);

        $validated = $this->validateApplication($request);

        if (!empty($validated['scope_of_work_id'])) {
            $validated['scope_of_work_details'] = $request->input('scope_detail_' . $validated['scope_of_work_id'], '');
        }

        $validated['applies_to'] = $request->boolean('skip_locational') ? 'SKIP_LC' : '';

        DB::beginTransaction();
        try {
            $validated['total_estimated_cost'] = ($validated['building_cost'] ?? 0) +
                ($validated['electrical_cost'] ?? 0) +
                ($validated['mechanical_cost'] ?? 0) +
                ($validated['electronics_cost'] ?? 0) +
                ($validated['plumbing_cost'] ?? 0) +
                ($validated['other_equipment_cost'] ?? 0);

            $application->update($validated);

            $application->applicationOccupancyGroups()->delete();
            $this->saveOccupancyGroups($application, $request);

            DB::commit();

            return redirect()->route('applications.show', $application)
                ->with('success', 'Application updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function submit(Application $application)
    {
        if ($application->status !== 'draft') {
            return back()->with('error', 'Only draft applications can be submitted.');
        }

        $skipLC = $application->applies_to === 'SKIP_LC';

        if ($skipLC) {
            $application->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            activity()->causedBy(Auth::user())->performedOn($application)->log('Application submitted — skipped Locational Clearance, routed to Engineering Assessment');
        } else {
            $application->update([
                'status' => 'for_zoning_assessment',
                'submitted_at' => now(),
            ]);
            activity()->causedBy(Auth::user())->performedOn($application)->log('Application submitted — routed to Planning Office for Zoning Assessment');
        }

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($application));

        $msg = $skipLC
            ? 'Application submitted. Routed directly to Engineering Assessment.'
            : 'Application submitted. Routed to Planning Office for Zoning Assessment.';

        return back()->with('success', $msg);
    }

    public function cancel(Request $request, Application $application)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (in_array($application->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Cannot cancel an application that has been paid or has a permit generated.');
        }

        $application->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        activity()->causedBy(Auth::user())->performedOn($application)->log('Application cancelled');

        return redirect()->route('applications.index')->with('warning', 'Application has been cancelled.');
    }

    public function printForm(Application $application)
    {
        $application->load([
            'permitType', 'applicationType', 'scopeOfWork', 'formOfOwnership',
            'applicantProvince', 'applicantCity', 'applicantBarangay', 'buildingBarangay',
            'landClassification', 'applicationOccupancyGroups.occupancyGroup',
            'applicationOccupancyGroups.occupancySubGroup',
        ]);

        return view('pdf.application-form', compact('application'));
    }

    private function getFormData(?int $permitTypeId = null): array
    {
        $sfcCityId = City::where('name', 'like', '%SAN FERNANDO%')->where('province_id', 3)->value('id') ?? 71;

        $appTypesQuery = ApplicationType::where('is_active', true);
        if ($permitTypeId) {
            $appTypesQuery->where('permit_type_id', $permitTypeId);
        }

        return [
            'applicationTypes' => $appTypesQuery->orderBy('sort_order')->get(),
            'scopeOfWorks' => ScopeOfWork::where('is_active', true)->orderBy('sort_order')->get(),
            'formOfOwnerships' => FormOfOwnership::where('is_active', true)->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'cities' => City::where('is_active', true)->orderBy('name')->get(),
            'barangays' => Barangay::where('is_active', true)->orderBy('name')->get(),
            'sfcBarangays' => Barangay::where('city_id', $sfcCityId)->where('is_active', true)->orderBy('name')->get(),
            'occupancyGroups' => OccupancyGroup::with('subGroups')->where('is_active', true)->orderBy('sort_order')->get(),
            'landClassifications' => LandClassification::where('is_active', true)->get(),
        ];
    }

    private function validateApplication(Request $request): array
    {
        return $request->validate([
            'application_type_id' => 'required|exists:application_types,id',
            'complexity' => 'required|in:Simple,Complex',
            'applies_to' => 'nullable|string|max:50',
            // Applicant
            'applicant_first_name' => 'required|string|max:255',
            'applicant_middle_name' => 'nullable|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_suffix' => 'nullable|string|max:20',
            'applicant_tin' => 'required|string|max:50',
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
            'scope_of_work_id' => 'required|exists:scope_of_works,id',
            'scope_of_work_details' => 'nullable|string|max:1000',
            // Building Location
            'lot_no' => 'required|string|max:50',
            'block_no' => 'required|string|max:50',
            'tct_no' => 'required|string|max:100',
            'tax_dec_no' => 'required|string|max:100',
            'land_classification_id' => 'required|exists:land_classifications,id',
            'building_street' => 'required|string|max:255',
            'building_barangay_id' => 'required|exists:barangays,id',
            // Building Specs
            'no_of_storeys' => 'required|integer|min:1',
            'no_of_units' => 'required|integer|min:1',
            'occupancy_classified' => 'nullable|string|max:255',
            'total_floor_area' => 'required|numeric|min:0',
            'lot_area' => 'required|numeric|min:0',
            // Cost Estimates
            'building_cost' => 'required|numeric|min:0',
            'electrical_cost' => 'required|numeric|min:0',
            'mechanical_cost' => 'required|numeric|min:0',
            'electronics_cost' => 'required|numeric|min:0',
            'plumbing_cost' => 'required|numeric|min:0',
            'other_equipment_cost' => 'nullable|numeric|min:0',
            'equipment_cost_1' => 'nullable|numeric|min:0',
            'equipment_cost_2' => 'nullable|numeric|min:0',
            'equipment_cost_3' => 'nullable|numeric|min:0',
            'equipment_cost_4' => 'nullable|numeric|min:0',
            // Timeline
            'proposed_construction_date' => 'required|date',
            'expected_completion_date' => 'required|date',
            // FSEC
            'fsec_no' => 'nullable|string|max:100',
            'fsec_issued_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
            // Engineer/Architect
            'engineer_name' => 'required|string|max:255',
            'engineer_prc_no' => 'required|string|max:50',
            'engineer_prc_validity' => 'required|date',
            'engineer_ptr_no' => 'required|string|max:50',
            'engineer_ptr_date_issued' => 'required|date',
            'engineer_ptr_issued_at' => 'required|string|max:255',
            'engineer_tin' => 'required|string|max:50',
            'engineer_address' => 'required|string|max:255',
            'engineer_date_signed' => 'required|date',
            // Owner
            'owner_name' => 'nullable|string|max:255',
            'owner_address' => 'nullable|string|max:255',
            'owner_govt_id' => 'nullable|string|max:100',
            'owner_id_date_issued' => 'nullable|date',
            'owner_id_place_issued' => 'nullable|string|max:255',
            'owner_date_signed' => 'nullable|date',
            // Electrical
            'include_electrical' => 'boolean',
            'total_connected_load' => 'nullable|numeric|min:0',
            'total_transformer_capacity' => 'nullable|numeric|min:0',
            'total_generator_capacity' => 'nullable|numeric|min:0',
            // PEE
            'pee_name' => 'nullable|string|max:255',
            'pee_prc_no' => 'nullable|string|max:50',
            'pee_prc_validity' => 'nullable|date',
            'pee_date_signed' => 'nullable|date',
            'pee_ptr_no' => 'nullable|string|max:50',
            'pee_ptr_date_issued' => 'nullable|date',
            'pee_ptr_issued_at' => 'nullable|string|max:255',
            'pee_address' => 'nullable|string|max:255',
            'pee_tin' => 'nullable|string|max:50',
            // SEW
            'sew_profession' => 'nullable|string|max:50',
            'sew_name' => 'nullable|string|max:255',
            'sew_prc_no' => 'nullable|string|max:50',
            'sew_prc_validity' => 'nullable|date',
            'sew_date_signed' => 'nullable|date',
            'sew_ptr_no' => 'nullable|string|max:50',
            'sew_ptr_date_issued' => 'nullable|date',
            'sew_ptr_issued_at' => 'nullable|string|max:255',
            'sew_address' => 'nullable|string|max:255',
            'sew_tin' => 'nullable|string|max:50',
        ]);
    }

    private function saveOccupancyGroups(Application $application, Request $request): void
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
                'application_id' => $application->id,
                'occupancy_group_id' => $subGroup->occupancy_group_id,
                'occupancy_sub_group_id' => $subGroup->id,
                'others_text' => $request->input("sub_group_{$subGroup->id}_others"),
            ]);
        }
    }
}
