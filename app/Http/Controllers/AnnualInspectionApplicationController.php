<?php

namespace App\Http\Controllers;

use App\Models\AnnualInspectionApplication;
use App\Models\AnnualInspectionEquipmentItem;
use App\Models\Barangay;
use App\Models\City;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use App\Models\PermitType;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class AnnualInspectionApplicationController extends Controller
{
    private function filteredQuery(Request $request): array
    {
        $query = AnnualInspectionApplication::where('status', '!=', 'cancelled');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%");
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

        return view('annual-inspection-applications.index', compact('applications', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        $aiPermitType = PermitType::where('code', 'AI')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = null;

        return view('annual-inspection-applications.form', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'occupancy_sub_group' => 'required|exists:occupancy_sub_groups,id',
        ], [
            'occupancy_sub_group.required' => 'Please select a Character of Occupancy.',
        ]);

        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $counter = DB::table('annual_inspection_applications')
                ->where('app_year', now()->year)
                ->where('app_month', now()->month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $appNumber = sprintf('AI-%s-%s-%05d', now()->format('Y'), now()->format('m'), $nextCounter);

            $equipment = $validated['equipment'] ?? [];
            unset($validated['equipment']);

            $application = AnnualInspectionApplication::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $nextCounter,
                'application_number' => $appNumber,
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
            ]));

            $this->syncEquipmentItems($application, $equipment);
            $this->saveOccupancyGroups($application, $request);

            DB::commit();

            return redirect()->route('annual-inspection-applications.show', $application)
                ->with('success', "Application {$appNumber} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }
    }

    public function show(AnnualInspectionApplication $annualInspectionApplication)
    {
        $annualInspectionApplication->load([
            'locationBarangay',
            'assessments.assessmentItems', 'billings', 'collections', 'permits',
            'annualInspectionPermitUnits.permit', 'equipmentItems',
            'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup',
        ]);

        $application = $annualInspectionApplication;

        return view('annual-inspection-applications.show', compact('application'));
    }

    public function edit(AnnualInspectionApplication $annualInspectionApplication)
    {
        $aiPermitType = PermitType::where('code', 'AI')->where('is_active', true)->firstOrFail();
        $data = $this->getFormData();
        $data['application'] = $annualInspectionApplication->load('applicationOccupancyGroups');

        return view('annual-inspection-applications.form', $data);
    }

    public function update(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate([
            'occupancy_sub_group' => 'required|exists:occupancy_sub_groups,id',
        ], [
            'occupancy_sub_group.required' => 'Please select a Character of Occupancy.',
        ]);

        $validated = $this->validateApplication($request);

        $equipment = $validated['equipment'] ?? [];
        unset($validated['equipment']);

        DB::beginTransaction();
        try {
            $annualInspectionApplication->update($validated);

            $this->syncEquipmentItems($annualInspectionApplication, $equipment);

            $annualInspectionApplication->applicationOccupancyGroups()->delete();
            $this->saveOccupancyGroups($annualInspectionApplication, $request);

            DB::commit();

            return redirect()->route('annual-inspection-applications.show', $annualInspectionApplication)
                ->with('success', 'Application updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($annualInspectionApplication->status !== 'draft') {
            return back()->with('error', 'Only draft applications can be submitted.');
        }

        $annualInspectionApplication->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        activity()->causedBy(Auth::user())->performedOn($annualInspectionApplication)
            ->log('Annual Inspection application submitted — routed to Engineering Assessment');

        $engineeringUsers = User::role(['engineering-officer', 'engineering-staff'])->get();
        Notification::send($engineeringUsers, new ApplicationSubmittedNotification($annualInspectionApplication));

        return back()->with('success', 'Application submitted. Routed to Engineering Assessment.');
    }

    public function revertSubmission(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($annualInspectionApplication->status !== 'submitted') {
            return back()->with('error', 'Only submitted applications can have their submission reverted.');
        }

        if ($annualInspectionApplication->assessments()->where('status', 'finalized')->exists()) {
            return back()->with('error', 'Cannot revert: engineering assessment has already started.');
        }

        DB::transaction(function () use ($annualInspectionApplication) {
            $annualInspectionApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($annualInspectionApplication)->log('Annual Inspection application submission reverted to draft');

        return back()->with('success', 'Application submission reverted to draft.');
    }

    public function cancel(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (in_array($annualInspectionApplication->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Cannot cancel an application that has been paid or has a permit generated.');
        }

        $annualInspectionApplication->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        activity()->causedBy(Auth::user())->performedOn($annualInspectionApplication)->log('Application cancelled');

        return redirect()->route('annual-inspection-applications.index')->with('warning', 'Application has been cancelled.');
    }

    private function getFormData(): array
    {
        $sfcCityId = City::where('name', 'like', '%SAN FERNANDO%')->where('province_id', 3)->value('id') ?? 71;

        return [
            'sfcBarangays' => Barangay::where('city_id', $sfcCityId)->where('is_active', true)->orderBy('name')->get(),
            'equipmentCategories' => AnnualInspectionEquipmentItem::CATEGORIES,
            'occupancyGroups' => OccupancyGroup::with('subGroups')->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    private function validateApplication(Request $request): array
    {
        return $request->validate([
            'application_kind' => 'required|in:new,yearly',
            'owner_name' => 'required|string|max:255',
            'location_street' => 'required|string|max:255',
            'location_barangay_id' => 'required|exists:barangays,id',
            'occupancy_no' => 'nullable|string|max:100',
            'occupancy_issued_date' => 'nullable|date',
            'equipment' => 'nullable|array',
            'equipment.*.fee_code' => ['required_with:equipment', 'string', Rule::in(AnnualInspectionEquipmentItem::allCodes())],
            'equipment.*.quantity' => 'required_with:equipment|integer|min:1',
            'equipment.*.specification' => 'nullable|string|max:255',
        ]);
    }

    private function syncEquipmentItems(AnnualInspectionApplication $application, array $equipment): void
    {
        $application->equipmentItems()->delete();

        $sortOrder = 0;
        foreach ($equipment as $row) {
            if (empty($row['fee_code'])) {
                continue;
            }

            $application->equipmentItems()->create([
                'fee_code' => $row['fee_code'],
                'quantity' => $row['quantity'] ?? 1,
                'specification' => $row['specification'] ?? null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function saveOccupancyGroups(AnnualInspectionApplication $application, Request $request): void
    {
        $selectedId = $request->input('occupancy_sub_group');

        if (empty($selectedId)) {
            return;
        }

        $subGroup = OccupancySubGroup::find($selectedId);

        if (! $subGroup) {
            return;
        }

        $application->applicationOccupancyGroups()->create([
            'application_id' => null,
            'occupancy_group_id' => $subGroup->occupancy_group_id,
            'occupancy_sub_group_id' => $subGroup->id,
            'others_text' => $request->input("sub_group_{$subGroup->id}_others"),
        ]);
    }
}
