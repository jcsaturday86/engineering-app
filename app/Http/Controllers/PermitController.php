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
use App\Models\AnnualInspectionApplication;
use App\Models\AnnualInspectionPermitUnit;
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

    public function annualInspectionIndex(Request $request)
    {
        $query = AnnualInspectionApplication::with('permits')
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

    public function generateAi(AnnualInspectionApplication $annualInspectionApplication)
    {
        return $this->doGenerateAi($annualInspectionApplication);
    }

    // AI certificate group definitions: General+Electrical / Electronics / Machinery (minus
    // Elevators, Escalators, Aircon-Refrigeration) are bundled into one certificate each;
    // Aircon/Refrigeration bundles ALL units into one certificate; Elevators and
    // Escalators/Funiculars/Cable Cars each get one certificate PER unit.
    private const AI_GROUP_LABELS = [
        'GE' => 'General, Occupancy & Electrical Annual Inspection',
        'ELN' => 'Electronics Annual Inspection',
        'MACH' => 'Machinery Annual Inspection',
        'ACREF' => 'Air Conditioning & Refrigeration Annual Inspection',
        'ELEV' => 'Elevator Annual Inspection',
        'ESC' => 'Escalator/Funicular/Cable Car Annual Inspection',
    ];

    private const AI_ACREF_CODES = ['AINSP_FI_REFRIG', 'AINSP_FII_WINAC', 'AINSP_FIII_CENAC'];
    private const AI_ELEV_CODES = ['AINSP_FVI_PASS', 'AINSP_FVI_FRT', 'AINSP_FVI_DUMB', 'AINSP_FVI_CONST', 'AINSP_FVI_CAR'];
    private const AI_ESC_CODES = ['AINSP_FV_ESC', 'AINSP_FV_FUNIC', 'AINSP_FV_FUNIC_LM', 'AINSP_FV_CABLE', 'AINSP_FV_CABLE_LM'];

    /**
     * Partition an AI application's finalized assessment items into certificate groups.
     * Returns an ordered list of ['group_code', 'label', 'items', 'amount', 'per_unit'].
     */
    private function buildAiCertificateGroups(AnnualInspectionApplication $application): array
    {
        $assessment = $application->assessments()
            ->where('assessment_type', 'mechanical')
            ->with(['assessmentItems' => fn ($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $items = $assessment ? $assessment->assessmentItems : collect();

        $geItems = $items->filter(fn ($i) => in_array($i->feeCategory?->code, ['AINSP_GEN', 'AINSP_ELEC'], true));
        $elnItems = $items->filter(fn ($i) => $i->feeCategory?->code === 'AINSP_ELECTRONICS');
        $mechItems = $items->filter(fn ($i) => $i->feeCategory?->code === 'AINSP_MECH');

        $acrefItems = $mechItems->filter(fn ($i) => in_array($i->fee_code, self::AI_ACREF_CODES, true));
        $elevItems = $mechItems->filter(fn ($i) => in_array($i->fee_code, self::AI_ELEV_CODES, true));
        $escItems = $mechItems->filter(fn ($i) => in_array($i->fee_code, self::AI_ESC_CODES, true));
        $machItems = $mechItems->reject(fn ($i) => in_array($i->fee_code, [...self::AI_ACREF_CODES, ...self::AI_ELEV_CODES, ...self::AI_ESC_CODES], true));

        $groups = [];

        $bundle = function (string $code, $bundleItems) use (&$groups) {
            if ($bundleItems->isEmpty()) {
                return;
            }
            $groups[] = [
                'group_code' => $code,
                'label' => self::AI_GROUP_LABELS[$code],
                'items' => $bundleItems->values(),
                'amount' => round($bundleItems->sum('amount') + $bundleItems->sum('inspection_fee'), 2),
                'per_unit' => false,
            ];
        };

        $bundle('GE', $geItems);
        $bundle('ELN', $elnItems);
        $bundle('MACH', $machItems);
        $bundle('ACREF', $acrefItems);

        foreach ($elevItems as $item) {
            $groups[] = [
                'group_code' => 'ELEV',
                'label' => self::AI_GROUP_LABELS['ELEV'],
                'items' => collect([$item]),
                'amount' => round($item->amount + $item->inspection_fee, 2),
                'per_unit' => true,
            ];
        }

        foreach ($escItems as $item) {
            $groups[] = [
                'group_code' => 'ESC',
                'label' => self::AI_GROUP_LABELS['ESC'],
                'items' => collect([$item]),
                'amount' => round($item->amount + $item->inspection_fee, 2),
                'per_unit' => true,
            ];
        }

        return $groups;
    }

    private function doGenerateAi(AnnualInspectionApplication $application)
    {
        if ($application->status !== 'paid') {
            return back()->with('error', 'Application must be paid before generating permit.');
        }

        if ($application->permits()->onlyTrashed()->where('status', 'revoked')->exists()) {
            return back()->with('error', "This application's permit was revoked. Restore the previous permit instead of generating a new one.");
        }

        $groups = $this->buildAiCertificateGroups($application);

        if (empty($groups)) {
            return back()->with('error', 'No assessed items found to generate certificates from.');
        }

        $permitType = PermitType::where('code', 'AI')->firstOrFail();
        $buildingOfficial = Signatory::where('role', 'building_official')->where('is_active', true)->first();

        DB::transaction(function () use ($application, $permitType, $buildingOfficial, $groups) {
            $counter = Permit::withTrashed()
                ->where('permit_type_id', $permitType->id)
                ->where('permit_year', now()->year)
                ->count();

            foreach ($groups as $group) {
                $counter++;
                $permitNumber = sprintf('AI-%s-%s-%05d', now()->format('Y'), now()->format('m'), $counter);

                $permit = Permit::create([
                    'applicationable_type' => 'ai',
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

                AnnualInspectionPermitUnit::create([
                    'annual_inspection_application_id' => $application->id,
                    'assessment_item_id' => $group['per_unit'] ? $group['items']->first()->id : null,
                    'group_code' => $group['group_code'],
                    'description' => $group['per_unit'] ? $group['items']->first()->description : $group['label'],
                    'quantity' => $group['items']->count(),
                    'amount' => $group['amount'],
                    'permit_id' => $permit->id,
                    'generated_at' => now(),
                ]);
            }

            $application->update([
                'status' => 'permit_generated',
                'issued_date' => now()->toDateString(),
            ]);

            activity()->causedBy(Auth::user())->performedOn($application)->log('Permit generated');
        });

        if ($application->client_user_id) {
            $permit = Permit::where('applicationable_type', 'ai')
                ->where('applicationable_id', $application->id)
                ->latest()->first();
            $application->clientUser->notify(new ApplicationApprovedNotification($application, $permit));
        }

        return back()->with('success', 'Permit certificates generated successfully.');
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
            'AI' => 'ai',
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

    public function revertGenerateAi(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($annualInspectionApplication->status !== 'permit_generated') {
            return back()->with('error', 'Only applications with generated permits can have them revoked.');
        }

        DB::transaction(function () use ($annualInspectionApplication, $request) {
            $annualInspectionApplication->permits()->get()->each(function ($permit) use ($request) {
                $permit->update([
                    'status' => 'revoked',
                    'revoke_reason' => $request->input('reason'),
                ]);
                $permit->delete();
            });

            $annualInspectionApplication->update([
                'status' => 'paid',
                'issued_date' => null,
            ]);
        });

        activity()->causedBy(Auth::user())->performedOn($annualInspectionApplication)
            ->withProperties(['reason' => $request->input('reason')])
            ->log('Permit generation reverted');

        return back()->with('success', 'Permit generation reverted.');
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

    public function restoreRevokeAi(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $revokedPermits = $annualInspectionApplication->permits()->onlyTrashed()->where('status', 'revoked')->get();

        if ($revokedPermits->isEmpty()) {
            return back()->with('error', 'No revoked permits found to restore.');
        }

        DB::transaction(function () use ($annualInspectionApplication, $revokedPermits) {
            $latestIssuedDate = null;
            foreach ($revokedPermits as $permit) {
                $permit->restore();
                $permit->update(['status' => 'generated']);
                $latestIssuedDate = $permit->issued_date;
            }

            $annualInspectionApplication->update([
                'status' => 'permit_generated',
                'issued_date' => $latestIssuedDate,
            ]);
        });

        activity()->causedBy(Auth::user())->performedOn($annualInspectionApplication)->log('Permit revocation reversed');

        return back()->with('success', 'Permit certificates restored successfully.');
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

        // AI-specific: each Permit corresponds to one certificate group (see
        // buildAiCertificateGroups()) — load its bridge-table unit and, for bundle
        // certificates, the full itemized list of items belonging to that same group.
        $aiUnit = null;
        $aiGroupItems = collect();
        $aiGroupLabel = '';
        if ($application instanceof AnnualInspectionApplication) {
            $aiUnit = AnnualInspectionPermitUnit::where('permit_id', $permit->id)->with('assessmentItem')->first();
            $aiGroupLabel = self::AI_GROUP_LABELS[$aiUnit->group_code ?? ''] ?? '';

            if ($aiUnit && ! $aiUnit->assessment_item_id) {
                $groups = $this->buildAiCertificateGroups($application);
                $matchedGroup = collect($groups)->first(fn ($g) => $g['group_code'] === $aiUnit->group_code && ! $g['per_unit']);
                $aiGroupItems = $matchedGroup['items'] ?? collect();
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
            'AI' => 'pdf.annual-inspection-permit',
            default => 'pdf.building-permit',
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, compact('permit', 'application', 'signatories', 'settings', 'sealImage', 'dpwhLogo', 'qrImage', 'aiUnit', 'aiGroupItems', 'aiGroupLabel'));
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
