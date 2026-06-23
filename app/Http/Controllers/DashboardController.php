<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $stats = [
            'total_applications' => Application::whereYear('created_at', $currentYear)->count(),
            'pending_applications' => Application::whereNotIn('status', ['cancelled', 'released'])->whereIn('status', ['draft', 'submitted'])->count(),
            'approved_applications' => Application::whereYear('created_at', $currentYear)->where('status', 'released')->count(),
            'for_assessment' => Application::whereIn('status', ['submitted', 'zoning_assessed'])->count(),
            'for_payment' => Application::where('status', 'billed')->count(),
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
            ->whereYear('created_at', $currentYear)
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

        $recentApplications = Application::with('permitType')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.index', compact('stats', 'revenueData', 'recentApplications'));
    }
}
