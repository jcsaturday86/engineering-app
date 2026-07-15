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
    public function index(Request $request)
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

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('signage-applications.index', compact('applications', 'year'));
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
        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $counter = DB::table('signage_applications')
                ->where('app_year', now()->year)
                ->where('app_month', now()->month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $appNumber = sprintf('SGP-%s-%s-%05d', now()->format('Y'), now()->format('m'), $nextCounter);

            $application = SignageApplication::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $nextCounter,
                'application_number' => $appNumber,
                'status' => 'draft',
                'source' => 'walk_in',
                'entered_by' => Auth::id(),
            ]));

            DB::commit();

            return redirect()->route('signage-applications.show', $application)
                ->with('success', "Application {$appNumber} created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create application: ' . $e->getMessage());
        }
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
        $validated = $this->validateApplication($request);

        DB::beginTransaction();
        try {
            $signageApplication->update($validated);

            DB::commit();

            return redirect()->route('signage-applications.show', $signageApplication)
                ->with('success', 'Application updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update application: ' . $e->getMessage());
        }
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

    private function getFormData(): array
    {
        $sfcCityId = City::where('name', 'like', '%SAN FERNANDO%')->where('province_id', 3)->value('id') ?? 71;

        return [
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
            // Misc
            'remarks' => 'nullable|string|max:1000',
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
