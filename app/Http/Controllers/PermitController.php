<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\DemolitionApplication;
use App\Models\OccupancyApplication;
use App\Models\Permit;
use App\Models\PermitType;
use App\Models\Signatory;
use App\Models\FencingApplication;
use App\Models\MechanicalApplication;
use App\Models\MechanicalPermitUnit;
use App\Models\SignageApplication;
use App\Notifications\ApplicationApprovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PermitController extends Controller
{
    public function buildingIndex(Request $request)
    {
        $query = Application::with('permitType', 'permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released']);

        $this->applyPermitFilters($query, $request);

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        $type = 'building';
        return view('permits.index', compact('applications', 'type', 'year'));
    }

    public function occupancyIndex(Request $request)
    {
        $query = OccupancyApplication::with('applicationType', 'permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released']);

        $this->applyPermitFilters($query, $request);

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        $type = 'occupancy';
        return view('permits.index', compact('applications', 'type', 'year'));
    }

    public function demolitionIndex(Request $request)
    {
        $query = DemolitionApplication::with('permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released']);

        $this->applyPermitFilters($query, $request);

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        $type = 'demolition';
        return view('permits.index', compact('applications', 'type', 'year'));
    }

    public function signageIndex(Request $request)
    {
        $query = SignageApplication::with('permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released']);

        $this->applyPermitFilters($query, $request);

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        $type = 'signage';
        return view('permits.index', compact('applications', 'type', 'year'));
    }

    public function fencingIndex(Request $request)
    {
        $query = FencingApplication::with('permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released']);

        $this->applyPermitFilters($query, $request);

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        $type = 'fencing';
        return view('permits.index', compact('applications', 'type', 'year'));
    }

    public function mechanicalIndex(Request $request)
    {
        $query = MechanicalApplication::with('permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'revoked') {
                $query->where('status', 'paid')->whereHas('permits', function ($q) {
                    $q->withTrashed()->where('status', 'revoked');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        $type = 'mechanical';
        return view('permits.index', compact('applications', 'type', 'year'));
    }

    public function zoningIndex(Request $request)
    {
        $query = Application::with('zoningAssessment')
            ->whereIn('status', ['paid', 'permit_generated', 'released'])
            ->where(function ($q) {
                $q->whereNull('applies_to')->orWhere('applies_to', '!=', 'SKIP_LC');
            });

        $this->applyPermitFilters($query, $request);

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $query->whereYear('created_at', $year);

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('permits.zoning-index', compact('applications', 'year'));
    }

    public function generateZoningDocuments(Application $application)
    {
        if (! in_array($application->status, ['paid', 'permit_generated', 'released'])) {
            return back()->with('error', 'Application must be paid before generating zoning documents.');
        }

        if ($application->applies_to === 'SKIP_LC') {
            return back()->with('error', 'Locational Clearance was skipped for this application; zoning documents are not applicable.');
        }

        $zoningAssessment = $application->zoningAssessment;

        if (! $zoningAssessment) {
            return back()->with('error', 'No zoning assessment found for this application.');
        }

        if ($zoningAssessment->decision_no) {
            return back()->with('error', 'Zoning documents have already been generated for this application.');
        }

        DB::transaction(function () use ($zoningAssessment, $application) {
            $decisionNo = (int) \App\Models\ZoningAssessment::whereNotNull('decision_no')->max('decision_no') + 1;

            $zoningAssessment->update([
                'decision_no' => $decisionNo,
                'certificate_date' => now()->toDateString(),
            ]);

            activity()->causedBy(Auth::user())->performedOn($application)->log('Zoning documents generated');
        });

        return back()->with('success', 'Zoning documents generated successfully.');
    }

    private function applyPermitFilters($query, Request $request): void
    {
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
            if ($request->status === 'revoked') {
                $query->where('status', 'paid')->whereHas('permits', function ($q) {
                    $q->withTrashed()->where('status', 'revoked');
                });
            } else {
                $query->where('status', $request->status);
            }
        }
    }

    // BP permit generation
    public function generate(Application $application)
    {
        return $this->doGenerate($application, 'BP');
    }

    // OP permit generation
    public function generateOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doGenerate($occupancyApplication, 'OP');
    }

    // DP permit generation
    public function generateDp(DemolitionApplication $demolitionApplication)
    {
        return $this->doGenerate($demolitionApplication, 'DP');
    }

    // SGP permit generation
    public function generateSgp(SignageApplication $signageApplication)
    {
        return $this->doGenerate($signageApplication, 'SGP');
    }

    public function generateFp(FencingApplication $fencingApplication)
    {
        return $this->doGenerate($fencingApplication, 'FP');
    }

    // MP permit generation — MP-only: one application can emit several Permit rows at once
    // (all Air Conditioning items bundled into one, one permit per Machinery/Escalator/Elevator/
    // Generator Set item), so it cannot reuse the shared single-permit doGenerate().
    public function generateMp(MechanicalApplication $mechanicalApplication)
    {
        return $this->doGenerateMp($mechanicalApplication);
    }

    private function doGenerate(PermitApplicationContract $application, string $permitCode)
    {
        if ($application->status !== 'paid') {
            return back()->with('error', 'Application must be paid before generating permit.');
        }

        if ($application->permits()->onlyTrashed()->where('status', 'revoked')->exists()) {
            return back()->with('error', "This application's permit was revoked. Restore the previous permit instead of generating a new one.");
        }

        $permitType = PermitType::where('code', $permitCode)->firstOrFail();
        $morphType = match ($permitCode) {
            'OP' => 'op',
            'DP' => 'dp',
            'SGP' => 'sgp',
            'FP' => 'fp',
            default => 'bp',
        };
        $buildingOfficial = Signatory::where('role', 'building_official')->where('is_active', true)->first();

        DB::transaction(function () use ($application, $permitType, $permitCode, $morphType, $buildingOfficial) {
            $counter = Permit::withTrashed()
                    ->where('permit_type_id', $permitType->id)
                    ->where('permit_year', now()->year)
                    ->count() + 1;

            $permitNumber = sprintf('%s-%s-%s-%05d', $permitCode, now()->format('Y'), now()->format('m'), $counter);

            Permit::create([
                'applicationable_type' => $morphType,
                'applicationable_id' => $application->id,
                'application_id' => $application->id,
                'permit_type_id' => $permitType->id,
                'permit_year' => now()->year,
                'permit_month' => now()->month,
                'permit_counter' => $counter,
                'permit_number' => $permitNumber,
                'verification_token' => (string) \Illuminate\Support\Str::uuid(),
                'issued_date' => now()->toDateString(),
                'processed_by' => Auth::id(),
                'status' => 'generated',
                'building_official_name' => $buildingOfficial?->name,
                'building_official_title' => $buildingOfficial?->title,
                'building_official_designation' => $buildingOfficial?->designation,
                'building_official_license_no' => $buildingOfficial?->license_no,
            ]);

            $application->update([
                'status' => 'permit_generated',
                'issued_date' => now()->toDateString(),
            ]);

            activity()->causedBy(Auth::user())->performedOn($application)->log('Permit generated');
        });

        if ($application->client_user_id) {
            $permit = Permit::where('applicationable_type', $morphType)
                ->where('applicationable_id', $application->id)
                ->latest()->first();
            $application->clientUser->notify(new ApplicationApprovedNotification($application, $permit));
        }

        return back()->with('success', 'Permit generated successfully.');
    }

    private function doGenerateMp(MechanicalApplication $mechanicalApplication)
    {
        if ($mechanicalApplication->status !== 'paid') {
            return back()->with('error', 'Application must be paid before generating permits.');
        }

        if ($mechanicalApplication->permits()->onlyTrashed()->where('status', 'revoked')->exists()) {
            return back()->with('error', "This application's permits were revoked. Restore the previous permits instead of generating new ones.");
        }

        $permitType = PermitType::where('code', 'MP')->firstOrFail();
        $buildingOfficial = Signatory::where('role', 'building_official')->where('is_active', true)->first();

        $assessment = $mechanicalApplication->assessments()
            ->where('assessment_type', 'mechanical')
            ->where('status', 'finalized')
            ->with(['assessmentItems' => fn ($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $items = $assessment?->assessmentItems ?? collect();

        if ($items->isEmpty()) {
            return back()->with('error', 'No finalized mechanical assessment items found for this application.');
        }

        $byGroup = $items->groupBy(fn ($item) => $item->feeCategory?->code ?? 'OTHER');

        // Build the list of units that will each become their own Permit: MP_AC items collapse
        // into exactly one bundled unit; every other group's items each become their own unit.
        $units = [];

        if ($byGroup->has('MP_AC')) {
            $acItems = $byGroup->get('MP_AC');
            $units[] = [
                'group_code' => 'AC',
                'description' => 'Air Conditioning / Refrigeration — ' . $acItems->pluck('description')->implode(', '),
                'quantity' => null,
                'amount' => round($acItems->sum('amount') + $acItems->sum('inspection_fee'), 2),
            ];
        }

        foreach (['MP_MACH' => 'MACH', 'MP_ESC' => 'ESC', 'MP_ELEV' => 'ELEV', 'MP_GENSET' => 'GENSET'] as $catCode => $groupCode) {
            foreach ($byGroup->get($catCode, collect()) as $item) {
                $units[] = [
                    'group_code' => $groupCode,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'amount' => round((float) $item->amount + (float) $item->inspection_fee, 2),
                ];
            }
        }

        if (empty($units)) {
            return back()->with('error', 'No finalized mechanical assessment items found for this application.');
        }

        DB::transaction(function () use ($mechanicalApplication, $permitType, $buildingOfficial, $units) {
            $counter = Permit::withTrashed()
                ->where('permit_type_id', $permitType->id)
                ->where('permit_year', now()->year)
                ->count();

            foreach ($units as $unit) {
                $counter++;
                $permitNumber = sprintf('MP-%s-%s-%05d', now()->format('Y'), now()->format('m'), $counter);

                $permit = Permit::create([
                    'applicationable_type' => 'mp',
                    'applicationable_id' => $mechanicalApplication->id,
                    'application_id' => $mechanicalApplication->id,
                    'permit_type_id' => $permitType->id,
                    'permit_year' => now()->year,
                    'permit_month' => now()->month,
                    'permit_counter' => $counter,
                    'permit_number' => $permitNumber,
                    'verification_token' => (string) \Illuminate\Support\Str::uuid(),
                    'issued_date' => now()->toDateString(),
                    'processed_by' => Auth::id(),
                    'status' => 'generated',
                    'building_official_name' => $buildingOfficial?->name,
                    'building_official_title' => $buildingOfficial?->title,
                    'building_official_designation' => $buildingOfficial?->designation,
                    'building_official_license_no' => $buildingOfficial?->license_no,
                ]);

                MechanicalPermitUnit::create([
                    'mechanical_application_id' => $mechanicalApplication->id,
                    'group_code' => $unit['group_code'],
                    'description' => $unit['description'],
                    'quantity' => $unit['quantity'],
                    'amount' => $unit['amount'],
                    'permit_id' => $permit->id,
                    'generated_at' => now(),
                ]);
            }

            $mechanicalApplication->update([
                'status' => 'permit_generated',
                'issued_date' => now()->toDateString(),
            ]);

            activity()->causedBy(Auth::user())->performedOn($mechanicalApplication)
                ->log('Mechanical permits generated (' . count($units) . ')');
        });

        if ($mechanicalApplication->client_user_id) {
            $permit = Permit::where('applicationable_type', 'mp')
                ->where('applicationable_id', $mechanicalApplication->id)
                ->latest()->first();
            $mechanicalApplication->clientUser->notify(new ApplicationApprovedNotification($mechanicalApplication, $permit));
        }

        return back()->with('success', count($units) . ' permit(s) generated successfully.');
    }

    // BP revert permit generation
    public function revertGenerate(Request $request, Application $application)
    {
        return $this->doRevertGenerate($request, $application);
    }

    // OP revert permit generation
    public function revertGenerateOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        return $this->doRevertGenerate($request, $occupancyApplication);
    }

    // DP revert permit generation
    public function revertGenerateDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        return $this->doRevertGenerate($request, $demolitionApplication);
    }

    // SGP revert permit generation
    public function revertGenerateSgp(Request $request, SignageApplication $signageApplication)
    {
        return $this->doRevertGenerate($request, $signageApplication);
    }

    public function revertGenerateFp(Request $request, FencingApplication $fencingApplication)
    {
        return $this->doRevertGenerate($request, $fencingApplication);
    }

    // MP revert permit generation — reuses the shared doRevertGenerate() as-is: it already loops
    // every permit on the application (`$application->permits()->get()->each(...)`), so it's
    // multi-permit-safe without modification. mechanical_permit_units rows keep their permit_id
    // untouched (only the Permit itself is revoked + soft-deleted), so restore needs no relinking.
    public function revertGenerateMp(Request $request, MechanicalApplication $mechanicalApplication)
    {
        return $this->doRevertGenerate($request, $mechanicalApplication);
    }

    private function doRevertGenerate(Request $request, PermitApplicationContract $application)
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($application->status !== 'permit_generated') {
            return back()->with('error', 'Only applications with a generated permit can have it revoked.');
        }

        DB::transaction(function () use ($application, $request) {
            $application->permits()->get()->each(function ($permit) use ($request) {
                $permit->update([
                    'status' => 'revoked',
                    'revoke_reason' => $request->input('reason'),
                ]);
                $permit->delete();
            });

            $application->update([
                'status' => 'paid',
                'issued_date' => null,
            ]);
        });

        activity()->causedBy(Auth::user())->performedOn($application)
            ->withProperties(['reason' => $request->input('reason')])
            ->log('Permit generation reverted');

        return back()->with('success', 'Permit generation reverted.');
    }

    // BP restore revoked permit
    public function restoreRevoke(Request $request, Application $application)
    {
        return $this->doRestoreRevoke($request, $application);
    }

    // OP restore revoked permit
    public function restoreRevokeOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        return $this->doRestoreRevoke($request, $occupancyApplication);
    }

    // DP restore revoked permit
    public function restoreRevokeDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        return $this->doRestoreRevoke($request, $demolitionApplication);
    }

    // SGP restore revoked permit
    public function restoreRevokeSgp(Request $request, SignageApplication $signageApplication)
    {
        return $this->doRestoreRevoke($request, $signageApplication);
    }

    public function restoreRevokeFp(Request $request, FencingApplication $fencingApplication)
    {
        return $this->doRestoreRevoke($request, $fencingApplication);
    }

    // MP restore revoked permits — MP-only: the shared doRestoreRevoke() restores exactly one
    // trashed permit (`->latest('deleted_at')->first()`), which breaks when an application has
    // several revoked permits at once. This restores all of them in a single action.
    public function restoreRevokeMp(Request $request, MechanicalApplication $mechanicalApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $permits = $mechanicalApplication->permits()->onlyTrashed()->where('status', 'revoked')->get();

        if ($permits->isEmpty()) {
            return back()->with('error', 'No revoked permits found to restore.');
        }

        DB::transaction(function () use ($mechanicalApplication, $permits) {
            $permits->each(function ($permit) {
                $permit->restore();
                $permit->update(['status' => 'generated']);
            });

            $mechanicalApplication->update([
                'status' => 'permit_generated',
                'issued_date' => $permits->first()->issued_date,
            ]);
        });

        activity()->causedBy(Auth::user())->performedOn($mechanicalApplication)
            ->log('Permit revocation reversed (' . $permits->count() . ' permits)');

        return back()->with('success', $permits->count() . ' permit(s) restored successfully.');
    }

    private function doRestoreRevoke(Request $request, PermitApplicationContract $application)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $permit = $application->permits()->onlyTrashed()->where('status', 'revoked')->latest('deleted_at')->first();

        if (! $permit) {
            return back()->with('error', 'No revoked permit found to restore.');
        }

        DB::transaction(function () use ($application, $permit) {
            $permit->restore();
            $permit->update(['status' => 'generated']);

            $application->update([
                'status' => 'permit_generated',
                'issued_date' => $permit->issued_date,
            ]);
        });

        activity()->causedBy(Auth::user())->performedOn($application)->log('Permit revocation reversed');

        return back()->with('success', 'Permit restored successfully.');
    }

    public function print(Permit $permit)
    {
        $permit->load('applicationable', 'permitType');

        $application = $permit->applicationable;
        $application->load(
            'applicationType',
            'applicantProvince', 'applicantCity',
            'applicantBarangay', 'buildingBarangay',
            'formOfOwnership',
            'applicationOccupancyGroups.occupancyGroup',
            'applicationOccupancyGroups.occupancySubGroup',
            'collections.collectionDetails'
        );

        // Load BP-specific relations if available
        if ($application instanceof Application) {
            $application->load('scopeOfWork');
        }

        // Load FP-specific relations (finalized assessment items)
        if ($application instanceof FencingApplication) {
            $application->load('assessments.assessmentItems');
        }

        // MP-specific: the specific equipment unit (and its assessed amount) this Permit
        // certificate covers — one application can have several Permits, each backed by its own
        // mechanical_permit_units row. For the bundled AC permit, also load the full itemized
        // list of AC assessment items so every unit's amount is shown, not just the summed total.
        $mechanicalUnit = null;
        $mechanicalAcItems = null;
        if ($application instanceof MechanicalApplication) {
            $mechanicalUnit = MechanicalPermitUnit::where('permit_id', $permit->id)->first();
            if ($mechanicalUnit && $mechanicalUnit->group_code === 'AC') {
                $mechanicalAcItems = $application->assessments()
                    ->where('assessment_type', 'mechanical')
                    ->with(['assessmentItems' => fn ($q) => $q->where('is_active', true)->whereHas('feeCategory', fn ($c) => $c->where('code', 'MP_AC'))])
                    ->first()?->assessmentItems ?? collect();
            }
        }

        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');
        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');

        $sealImage = null;
        if (! empty($settings['general.logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['general.logo'])) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($settings['general.logo']);
            $sealImage = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($settings['general.logo']));
        }

        $dpwhLogo = null;
        if (! empty($settings['general.dpwh_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['general.dpwh_logo'])) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($settings['general.dpwh_logo']);
            $dpwhLogo = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($settings['general.dpwh_logo']));
        } else {
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

        $template = match ($permit->permitType->code) {
            'OP' => 'pdf.occupancy-permit',
            'DP' => 'pdf.demolition-permit',
            'SGP' => 'pdf.signage-permit',
            'FP' => 'pdf.fencing-permit',
            'MP' => 'pdf.mechanical-permit',
            default => 'pdf.building-permit',
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, compact('permit', 'application', 'signatories', 'settings', 'sealImage', 'dpwhLogo', 'qrImage', 'mechanicalUnit', 'mechanicalAcItems'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("permit_{$permit->permit_number}.pdf");
    }

    public function zoningCertification(Application $application)
    {
        $application->load('zoningAssessment', 'collections.collectionDetails', 'buildingBarangay');
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');

        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');
        $sealImage = null;
        if (! empty($settings['general.logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['general.logo'])) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($settings['general.logo']);
            $sealImage = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($settings['general.logo']));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.zoning-certification', compact('application', 'signatories', 'sealImage', 'settings'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("zoning_cert_{$application->application_number}.pdf");
    }

    public function locationalClearance(Application $application)
    {
        $application->load('zoningAssessment', 'collections.collectionDetails', 'buildingBarangay', 'applicantBarangay', 'applicantCity');
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');

        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');
        $sealImage = null;
        if (! empty($settings['general.logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['general.logo'])) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($settings['general.logo']);
            $sealImage = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($settings['general.logo']));
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.locational-clearance', compact('application', 'signatories', 'sealImage', 'settings'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("locational_{$application->application_number}.pdf");
    }

    public function evaluationReport(Application $application)
    {
        $application->load('zoningAssessment', 'buildingBarangay', 'applicantBarangay', 'applicantCity');
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');

        $settings = \App\Models\Setting::general();
        $sealImage = \App\Models\Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.evaluation-report', compact('application', 'signatories', 'settings', 'sealImage'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("evaluation_{$application->application_number}.pdf");
    }
}
