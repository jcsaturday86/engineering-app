<?php

namespace App\Http\Controllers;

use App\Models\AnnualInspectionApplication;
use App\Models\Application;
use App\Models\Collection;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\SignageApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
}
