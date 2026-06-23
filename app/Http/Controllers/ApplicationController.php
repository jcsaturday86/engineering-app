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

        if ($request->filled('permit_type')) {
            $query->where('permit_type_id', $request->permit_type);
        }

        $applications = $query->latest()->paginate(20)->withQueryString();
        $permitTypes = PermitType::where('is_active', true)->get();

        return view('applications.index', compact('applications', 'permitTypes'));
    }

    public function create(Request $request)
    {
        $typeCode = $request->get('type', 'BP');
        $permitType = PermitType::where('code', $typeCode)->where('is_active', true)->firstOrFail();

        $data = $this->getFormData();
        $data['permitType'] = $permitType;
        $data['application'] = null;

        return view('applications.form', $data);
    }

    public function store(Request $request)
    {
        $validated = $this->validateApplication($request);

        $permitType = PermitType::findOrFail($validated['permit_type_id']);

        DB::beginTransaction();
        try {
            $counter = Application::where('permit_type_id', $permitType->id)
                    ->where('app_year', now()->year)
                    ->where('app_month', now()->month)
                    ->count() + 1;

            $appNumber = sprintf(
                '%s-%s-%s-%05d',
                $permitType->code,
                now()->format('Y'),
                now()->format('m'),
                $counter
            );

            $application = Application::create(array_merge($validated, [
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
                    ($validated['other_equipment_cost'] ?? 0),
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
        $data = $this->getFormData();
        $data['permitType'] = $application->permitType;
        $data['application'] = $application->load('applicationOccupancyGroups');

        return view('applications.form', $data);
    }

    public function update(Request $request, Application $application)
    {
        $validated = $this->validateApplication($request);

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

        $application->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        activity()->causedBy(Auth::user())->performedOn($application)->log('Application submitted');

        // Notify engineering officers
        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($application));

        return back()->with('success', 'Application submitted for processing.');
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.application-form', compact('application'));
        $pdf->setPaper('legal', 'portrait');

        return $pdf->stream("application_{$application->application_number}.pdf");
    }

    private function getFormData(): array
    {
        return [
            'applicationTypes' => ApplicationType::where('is_active', true)->orderBy('sort_order')->get(),
            'scopeOfWorks' => ScopeOfWork::where('is_active', true)->orderBy('sort_order')->get(),
            'formOfOwnerships' => FormOfOwnership::where('is_active', true)->get(),
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'cities' => City::where('is_active', true)->orderBy('name')->get(),
            'barangays' => Barangay::where('is_active', true)->orderBy('name')->get(),
            'occupancyGroups' => OccupancyGroup::with('subGroups')->where('is_active', true)->orderBy('sort_order')->get(),
            'landClassifications' => LandClassification::where('is_active', true)->get(),
        ];
    }

    private function validateApplication(Request $request): array
    {
        return $request->validate([
            'permit_type_id' => 'required|exists:permit_types,id',
            'application_type_id' => 'required|exists:application_types,id',
            'applicant_first_name' => 'required|string|max:255',
            'applicant_middle_name' => 'nullable|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_suffix' => 'nullable|string|max:20',
            'applicant_tin' => 'nullable|string|max:50',
            'applicant_contact_no' => 'nullable|string|max:20',
            'applicant_email' => 'nullable|email|max:255',
            'applicant_govt_id' => 'nullable|string|max:100',
            'applicant_id_date_issued' => 'nullable|date',
            'applicant_id_place_issued' => 'nullable|string|max:255',
            'enterprise_name' => 'nullable|string|max:255',
            'form_of_ownership_id' => 'nullable|exists:form_of_ownerships,id',
            'applicant_province_id' => 'nullable|exists:provinces,id',
            'applicant_city_id' => 'nullable|exists:cities,id',
            'applicant_barangay_id' => 'nullable|exists:barangays,id',
            'applicant_street' => 'nullable|string|max:255',
            'applicant_zip_code' => 'nullable|string|max:10',
            'project_title' => 'nullable|string|max:255',
            'scope_of_work_id' => 'nullable|exists:scope_of_works,id',
            'scope_of_work_details' => 'nullable|string|max:1000',
            'lot_no' => 'nullable|string|max:50',
            'block_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:100',
            'tax_dec_no' => 'nullable|string|max:100',
            'land_classification_id' => 'nullable|exists:land_classifications,id',
            'building_street' => 'nullable|string|max:255',
            'building_barangay_id' => 'nullable|exists:barangays,id',
            'no_of_storeys' => 'nullable|integer|min:1',
            'no_of_units' => 'nullable|integer|min:1',
            'total_floor_area' => 'nullable|numeric|min:0',
            'lot_area' => 'nullable|numeric|min:0',
            'building_cost' => 'nullable|numeric|min:0',
            'electrical_cost' => 'nullable|numeric|min:0',
            'mechanical_cost' => 'nullable|numeric|min:0',
            'electronics_cost' => 'nullable|numeric|min:0',
            'plumbing_cost' => 'nullable|numeric|min:0',
            'other_equipment_cost' => 'nullable|numeric|min:0',
            'proposed_construction_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
            'bp_number' => 'nullable|string|max:30',
            'bp_issued_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'engineer_name' => 'nullable|string|max:255',
            'engineer_prc_no' => 'nullable|string|max:50',
            'engineer_prc_validity' => 'nullable|date',
            'engineer_ptr_no' => 'nullable|string|max:50',
            'engineer_ptr_date_issued' => 'nullable|date',
            'engineer_ptr_issued_at' => 'nullable|string|max:255',
            'engineer_tin' => 'nullable|string|max:50',
            'engineer_address' => 'nullable|string|max:255',
            'engineer_date_signed' => 'nullable|date',
            'owner_name' => 'nullable|string|max:255',
            'owner_address' => 'nullable|string|max:255',
            'owner_govt_id' => 'nullable|string|max:100',
            'owner_id_date_issued' => 'nullable|date',
            'owner_date_signed' => 'nullable|date',
            'include_electrical' => 'boolean',
            'total_connected_load' => 'nullable|numeric|min:0',
            'total_transformer_capacity' => 'nullable|numeric|min:0',
            'total_generator_capacity' => 'nullable|numeric|min:0',
        ]);
    }

    private function saveOccupancyGroups(Application $application, Request $request): void
    {
        $subGroups = OccupancySubGroup::with('occupancyGroup')->where('is_active', true)->get();

        foreach ($subGroups as $subGroup) {
            $fieldName = "sub_group_{$subGroup->id}";
            if ($request->has($fieldName)) {
                $application->applicationOccupancyGroups()->create([
                    'occupancy_group_id' => $subGroup->occupancy_group_id,
                    'occupancy_sub_group_id' => $subGroup->id,
                    'others_text' => $request->input("sub_group_{$subGroup->id}_others"),
                ]);
            }
        }
    }
}
