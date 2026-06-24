<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationType;
use App\Models\Barangay;
use App\Models\City;
use App\Models\FormOfOwnership;
use App\Models\LandClassification;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use App\Models\Permit;
use App\Models\PermitType;
use App\Models\Province;
use App\Models\ScopeOfWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnlineApplicationController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $applications = Application::with('permitType')
            ->where('client_user_id', $user->id)
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => Application::where('client_user_id', $user->id)->count(),
            'pending' => Application::where('client_user_id', $user->id)->whereNotIn('status', ['cancelled', 'released', 'permit_generated'])->count(),
            'approved' => Application::where('client_user_id', $user->id)->whereIn('status', ['permit_generated', 'released'])->count(),
        ];

        return view('online.dashboard', compact('applications', 'stats'));
    }

    public function create()
    {
        $permitTypes = PermitType::where('is_active', true)->get();
        $applicationTypes = ApplicationType::where('is_active', true)->orderBy('sort_order')->get()->groupBy('permit_type_id');
        $scopeOfWorks = ScopeOfWork::where('is_active', true)->orderBy('sort_order')->get();
        $formOfOwnerships = FormOfOwnership::where('is_active', true)->get();
        $provinces = Province::where('is_active', true)->orderBy('name')->get();
        $cities = City::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::where('is_active', true)->orderBy('name')->get();
        $occupancyGroups = OccupancyGroup::with('subGroups')->where('is_active', true)->orderBy('sort_order')->get();
        $landClassifications = LandClassification::where('is_active', true)->get();

        return view('online.apply', compact(
            'permitTypes', 'applicationTypes', 'scopeOfWorks', 'formOfOwnerships',
            'provinces', 'cities', 'barangays', 'occupancyGroups', 'landClassifications'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'permit_type_id' => 'required|exists:permit_types,id',
            'application_type_id' => 'required|exists:application_types,id',
            'applicant_first_name' => 'required|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_contact_no' => 'nullable|string|max:20',
            'applicant_email' => 'nullable|email|max:255',
            'project_title' => 'nullable|string|max:255',
            'scope_of_work_id' => 'nullable|exists:scope_of_works,id',
            'building_cost' => 'nullable|numeric|min:0',
            'electrical_cost' => 'nullable|numeric|min:0',
            'mechanical_cost' => 'nullable|numeric|min:0',
            'electronics_cost' => 'nullable|numeric|min:0',
            'plumbing_cost' => 'nullable|numeric|min:0',
            'other_equipment_cost' => 'nullable|numeric|min:0',
        ]);

        $permitType = PermitType::findOrFail($validated['permit_type_id']);

        DB::beginTransaction();
        try {
            $counter = Application::where('permit_type_id', $permitType->id)
                    ->where('app_year', now()->year)
                    ->where('app_month', now()->month)
                    ->count() + 1;

            $appNumber = sprintf('%s-%s-%s-%05d', $permitType->code, now()->format('Y'), now()->format('m'), $counter);

            $application = Application::create(array_merge($validated, [
                'app_year' => now()->year,
                'app_month' => now()->month,
                'app_counter' => $counter,
                'application_number' => $appNumber,
                'status' => 'submitted',
                'source' => 'online',
                'entered_by' => Auth::id(),
                'client_user_id' => Auth::id(),
                'applicant_email' => Auth::user()->email,
                'submitted_at' => now(),
                'total_estimated_cost' => ($validated['building_cost'] ?? 0) + ($validated['electrical_cost'] ?? 0) +
                    ($validated['mechanical_cost'] ?? 0) + ($validated['electronics_cost'] ?? 0) +
                    ($validated['plumbing_cost'] ?? 0) + ($validated['other_equipment_cost'] ?? 0),
            ]));

            DB::commit();
            return redirect()->route('online.show', $application)->with('success', "Application {$appNumber} created.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function show(Application $application)
    {
        abort_if($application->client_user_id !== Auth::id(), 403);

        $application->load('permitType', 'applicationRequirements', 'permits', 'collections');

        return view('online.show', compact('application'));
    }

    public function uploadRequirements(Application $application)
    {
        abort_if($application->client_user_id !== Auth::id(), 403);

        $requirements = $application->applicationRequirements;

        return view('online.upload', compact('application', 'requirements'));
    }

    public function storeRequirement(Request $request, Application $application)
    {
        abort_if($application->client_user_id !== Auth::id(), 403);

        $request->validate([
            'requirement_name' => 'required|string|max:255',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $path = $request->file('file')->store('requirements/' . $application->id, 'public');

        ApplicationRequirement::create([
            'application_id' => $application->id,
            'requirement_name' => $request->requirement_name,
            'file_path' => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Requirement uploaded.');
    }

    public function track(Application $application)
    {
        abort_if($application->client_user_id !== Auth::id(), 403);

        $application->load('permitType', 'collections', 'permits');

        $timeline = [
            ['status' => 'draft', 'label' => 'Application Created', 'date' => $application->created_at],
            ['status' => 'submitted', 'label' => 'Submitted', 'date' => $application->submitted_at],
            ['status' => 'zoning_assessed', 'label' => 'Zoning Assessed', 'date' => null],
            ['status' => 'engineering_assessed', 'label' => 'Engineering Assessed', 'date' => $application->assessed_at],
            ['status' => 'billed', 'label' => 'Billed', 'date' => null],
            ['status' => 'paid', 'label' => 'Payment Received', 'date' => $application->paid_at],
            ['status' => 'permit_generated', 'label' => 'Permit Generated', 'date' => null],
            ['status' => 'released', 'label' => 'Released', 'date' => $application->released_at],
        ];

        return view('online.track', compact('application', 'timeline'));
    }

    public function downloadPermit(Application $application)
    {
        abort_if($application->client_user_id !== Auth::id(), 403);

        $permit = Permit::where('application_id', $application->id)->latest()->first();

        if (!$permit) {
            return back()->with('error', 'Permit not yet generated.');
        }

        $application->load('permitType', 'applicantBarangay', 'buildingBarangay', 'scopeOfWork',
            'applicationOccupancyGroups.occupancyGroup', 'applicationOccupancyGroups.occupancySubGroup',
            'collections.collectionDetails');

        $signatories = \App\Models\Signatory::where('is_active', true)->get()->keyBy('role');
        $template = $application->permitType->code === 'OP' ? 'pdf.occupancy-permit' : 'pdf.building-permit';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, compact('permit', 'signatories'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("permit_{$permit->permit_number}.pdf");
    }
}
