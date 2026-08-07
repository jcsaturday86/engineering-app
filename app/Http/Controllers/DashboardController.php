<?php

namespace App\Http\Controllers;

use App\Models\AnnualInspectionApplication;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\Collection;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\SignageApplication;
use App\Models\ZoningAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->hasRole('client')) {
            return redirect()->route('online.dashboard');
        }

        $user = auth()->user();
        $isPlanningOnly = $user->hasAnyRole(['planning-officer', 'planning-staff'])
            && ! $user->hasAnyRole(['super-admin', 'administrator', 'engineering-officer', 'engineering-staff']);

        if ($isPlanningOnly) {
            return $this->planningDashboard($request);
        }

        $isTreasuryOnly = $user->hasAnyRole(['treasury-officer', 'treasury-staff'])
            && ! $user->hasAnyRole(['super-admin', 'administrator', 'engineering-officer', 'engineering-staff']);

        if ($isTreasuryOnly) {
            return $this->treasuryDashboard($request);
        }

        $currentYear = now()->year;
        $currentMonth = now()->month;

        $chartYear = (int) $request->query('year', $currentYear);
        if ($chartYear > $currentYear) {
            $chartYear = $currentYear;
        }

        $bpTotal = Application::whereYear('created_at', $currentYear)->count();
        $opTotal = OccupancyApplication::whereYear('created_at', $currentYear)->count();

        $bpPending = Application::whereIn('status', ['draft', 'submitted', 'for_zoning_assessment'])->count();
        $opPending = OccupancyApplication::whereIn('status', ['draft', 'submitted'])->count();

        $bpApproved = Application::whereYear('created_at', $currentYear)->where('status', 'released')->count();
        $opApproved = OccupancyApplication::whereYear('created_at', $currentYear)->where('status', 'released')->count();

        $bpForAssessment = Application::whereIn('status', ['submitted', 'zoning_assessed'])->count();
        $opForAssessment = OccupancyApplication::whereIn('status', ['submitted', 'zoning_assessed'])->count();

        $bpForPayment = Application::where('status', 'billed')->count();
        $opForPayment = OccupancyApplication::where('status', 'billed')->count();

        // DP/FP/SGP/AI share an identical status flow with no zoning stage:
        // draft -> submitted -> engineering_assessed -> billed -> paid -> permit_generated -> released
        $extraTypes = [
            'dp' => DemolitionApplication::class,
            'fp' => FencingApplication::class,
            'sgp' => SignageApplication::class,
            'ai' => AnnualInspectionApplication::class,
        ];

        $extraTotal = 0;
        $extraPending = 0;
        $extraApproved = 0;
        $extraForAssessment = 0;
        $extraForPayment = 0;

        foreach ($extraTypes as $model) {
            $extraTotal += $model::whereYear('created_at', $currentYear)->count();
            $extraPending += $model::whereIn('status', ['draft', 'submitted'])->count();
            $extraApproved += $model::whereYear('created_at', $currentYear)->where('status', 'released')->count();
            $extraForAssessment += $model::where('status', 'submitted')->count();
            $extraForPayment += $model::where('status', 'billed')->count();
        }

        $stats = [
            'total_applications' => $bpTotal + $opTotal + $extraTotal,
            'pending_applications' => $bpPending + $opPending + $extraPending,
            'approved_applications' => $bpApproved + $opApproved + $extraApproved,
            'for_assessment' => $bpForAssessment + $opForAssessment + $extraForAssessment,
            'for_payment' => $bpForPayment + $opForPayment + $extraForPayment,
            'total_revenue' => Collection::where('status', 'active')
                ->whereYear('created_at', $currentYear)
                ->sum('amount_due'),
            'monthly_revenue' => Collection::where('status', 'active')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->sum('amount_due'),
            'daily_transactions' => Collection::where('status', 'active')
                ->whereDate('created_at', today())
                ->count(),
        ];

        $monthlyRevenue = Collection::where('status', 'active')
            ->whereYear('created_at', $chartYear)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount_due) as total')
            )
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month')
            ->toArray();

        $revenueData = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenueData[] = $monthlyRevenue[$i] ?? 0;
        }

        $monthlyBpTransactions = Collection::where('status', 'active')
            ->where('applicationable_type', 'bp')
            ->whereYear('created_at', $chartYear)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month')
            ->toArray();

        $monthlyOpTransactions = Collection::where('status', 'active')
            ->where('applicationable_type', 'op')
            ->whereYear('created_at', $chartYear)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month')
            ->toArray();

        $bpTransactionData = [];
        $opTransactionData = [];
        for ($i = 1; $i <= 12; $i++) {
            $bpTransactionData[] = $monthlyBpTransactions[$i] ?? 0;
            $opTransactionData[] = $monthlyOpTransactions[$i] ?? 0;
        }

        $extraTransactionData = [];
        foreach (array_keys($extraTypes) as $morphType) {
            $monthly = Collection::where('status', 'active')
                ->where('applicationable_type', $morphType)
                ->whereYear('created_at', $chartYear)
                ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->pluck('total', 'month')
                ->toArray();

            $data = [];
            for ($i = 1; $i <= 12; $i++) {
                $data[] = $monthly[$i] ?? 0;
            }
            $extraTransactionData[$morphType] = $data;
        }

        $bpRecent = Application::with('permitType')->latest()->take(10)->get()
            ->map(fn ($app) => (object) [
                'id' => $app->id,
                'type' => 'bp',
                'application_number' => $app->application_number,
                'applicant_full_name' => $app->applicant_full_name,
                'permit_type_code' => 'BP',
                'status' => $app->status,
                'created_at' => $app->created_at,
                'route' => route('applications.show', $app->id),
            ]);

        $opRecent = OccupancyApplication::latest()->take(10)->get()
            ->map(fn ($app) => (object) [
                'id' => $app->id,
                'type' => 'op',
                'application_number' => $app->application_number,
                'applicant_full_name' => $app->applicant_full_name,
                'permit_type_code' => 'OP',
                'status' => $app->status,
                'created_at' => $app->created_at,
                'route' => route('occupancy-applications.show', $app->id),
            ]);

        $extraRouteNames = [
            'dp' => 'demolition-applications.show',
            'fp' => 'fencing-applications.show',
            'sgp' => 'signage-applications.show',
            'ai' => 'annual-inspection-applications.show',
        ];
        $extraPermitCodes = ['dp' => 'DP', 'fp' => 'FP', 'sgp' => 'SGP', 'ai' => 'AI'];

        $extraRecent = collect();
        foreach ($extraTypes as $morphType => $model) {
            $extraRecent = $extraRecent->concat(
                $model::latest()->take(10)->get()->map(fn ($app) => (object) [
                    'id' => $app->id,
                    'type' => $morphType,
                    'application_number' => $app->application_number,
                    'applicant_full_name' => $morphType === 'ai' ? $app->owner_name : $app->applicant_full_name,
                    'permit_type_code' => $extraPermitCodes[$morphType],
                    'status' => $app->status,
                    'created_at' => $app->created_at,
                    'route' => route($extraRouteNames[$morphType], $app->id),
                ])
            );
        }

        $recentApplications = $bpRecent->concat($opRecent)->concat($extraRecent)->sortByDesc('created_at')->take(10);

        return view('dashboard.index', compact('stats', 'revenueData', 'bpTransactionData', 'opTransactionData', 'extraTransactionData', 'recentApplications', 'chartYear', 'currentYear'));
    }

    private function planningDashboard(Request $request)
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $chartYear = (int) $request->query('year', $currentYear);
        if ($chartYear > $currentYear) {
            $chartYear = $currentYear;
        }

        $excludeSkipLc = fn ($q) => $q->whereNull('applies_to')->orWhere('applies_to', '!=', 'SKIP_LC');

        $zoningStatuses = [
            'for_zoning_assessment', 'zoning_assessed', 'engineering_assessed',
            'billed', 'paid', 'permit_generated', 'released',
        ];

        $stats = [
            'pending_zoning_assessments' => Application::where('status', 'for_zoning_assessment')
                ->where($excludeSkipLc)
                ->count(),
            'zoning_assessed_this_month' => Assessment::where('applicationable_type', 'bp')
                ->where('assessment_type', 'zoning')
                ->where('status', 'finalized')
                ->whereMonth('finalized_at', $currentMonth)
                ->whereYear('finalized_at', $currentYear)
                ->count(),
            'zoning_permits_generated_month' => ZoningAssessment::whereNotNull('decision_no')
                ->whereMonth('certificate_date', $currentMonth)
                ->whereYear('certificate_date', $currentYear)
                ->count(),
            'zoning_permits_generated_total' => ZoningAssessment::whereNotNull('decision_no')->count(),
            'zoning_revenue_annual' => Assessment::where('applicationable_type', 'bp')
                ->where('assessment_type', 'zoning')
                ->whereYear('created_at', $currentYear)
                ->sum('total_amount'),
            'zoning_revenue_monthly' => Assessment::where('applicationable_type', 'bp')
                ->where('assessment_type', 'zoning')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->sum('total_amount'),
        ];

        $monthlyZoningAssessments = Assessment::where('applicationable_type', 'bp')
            ->where('assessment_type', 'zoning')
            ->where('status', 'finalized')
            ->whereYear('finalized_at', $chartYear)
            ->select(
                DB::raw('MONTH(finalized_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('MONTH(finalized_at)'))
            ->pluck('total', 'month')
            ->toArray();

        $zoningChartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $zoningChartData[] = $monthlyZoningAssessments[$i] ?? 0;
        }

        $recentZoningActivity = Application::with('permitType')
            ->whereIn('status', $zoningStatuses)
            ->where($excludeSkipLc)
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn ($app) => (object) [
                'id' => $app->id,
                'application_number' => $app->application_number,
                'applicant_full_name' => $app->applicant_full_name,
                'status' => $app->status,
                'updated_at' => $app->updated_at,
                'route' => $app->status === 'for_zoning_assessment'
                    ? route('zoning.assess', $app->id)
                    : route('permits.zoning'),
            ]);

        return view('dashboard.planning', compact('stats', 'zoningChartData', 'recentZoningActivity', 'chartYear', 'currentYear'));
    }

    private function treasuryDashboard(Request $request)
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $chartYear = (int) $request->query('year', $currentYear);
        if ($chartYear > $currentYear) {
            $chartYear = $currentYear;
        }

        $stats = [
            'daily_transactions' => Collection::where('status', 'active')
                ->whereDate('created_at', today())
                ->count(),
            'daily_revenue' => Collection::where('status', 'active')
                ->whereDate('created_at', today())
                ->sum('amount_due'),
            'monthly_revenue' => Collection::where('status', 'active')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->sum('amount_due'),
            'annual_revenue' => Collection::where('status', 'active')
                ->whereYear('created_at', $currentYear)
                ->sum('amount_due'),
        ];

        $monthlyRevenue = Collection::where('status', 'active')
            ->whereYear('created_at', $chartYear)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount_due) as total')
            )
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month')
            ->toArray();

        $revenueData = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenueData[] = $monthlyRevenue[$i] ?? 0;
        }

        $recentCollections = Collection::where('status', 'active')
            ->with('applicationable')
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn ($collection) => (object) [
                'id' => $collection->id,
                'or_number' => $collection->or_number,
                'application_number' => $collection->application?->application_number,
                'paid_by' => $collection->paid_by,
                'amount_received' => $collection->amount_received,
                'payment_mode' => $collection->payment_mode,
                'created_at' => $collection->created_at,
                'route' => route('collections.receipt', $collection->id),
            ]);

        return view('dashboard.treasury', compact('stats', 'revenueData', 'recentCollections', 'chartYear', 'currentYear'));
    }
}
