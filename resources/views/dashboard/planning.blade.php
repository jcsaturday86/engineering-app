@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <span class="text-gray-900 font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Pending Zoning Assessment</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($stats['pending_zoning_assessments']) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Awaiting assessment</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Zoning Assessed</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['zoning_assessed_this_month']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-map-marked-alt text-blue-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ now()->format('F Y') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Zoning Permits Generated</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['zoning_permits_generated_month']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-certificate text-green-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ number_format($stats['zoning_permits_generated_total']) }} total</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Zoning Revenue</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">&#8369;{{ number_format($stats['zoning_revenue_monthly'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-emerald-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ now()->format('F Y') }}</p>
        </div>
    </div>

    {{-- Revenue Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-5 text-white">
            <p class="text-sm text-blue-100">Annual Zoning Assessment Revenue</p>
            <p class="text-2xl font-bold mt-1">&#8369;{{ number_format($stats['zoning_revenue_annual'], 2) }}</p>
            <p class="text-xs text-blue-200 mt-2">{{ now()->year }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-xl p-5 text-white">
            <p class="text-sm text-emerald-100">Monthly Zoning Assessment Revenue</p>
            <p class="text-2xl font-bold mt-1">&#8369;{{ number_format($stats['zoning_revenue_monthly'], 2) }}</p>
            <p class="text-xs text-emerald-200 mt-2">{{ now()->format('F Y') }}</p>
        </div>
    </div>

    {{-- Chart Year Navigator --}}
    <div class="flex items-center justify-center gap-3">
        <a href="{{ route('dashboard', ['year' => $chartYear - 1]) }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
            <i class="fas fa-chevron-left text-xs"></i>
        </a>
        <span class="text-sm font-semibold text-gray-900 w-16 text-center">{{ $chartYear }}</span>
        @if($chartYear < $currentYear)
            <a href="{{ route('dashboard', ['year' => $chartYear + 1]) }}"
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition">
                <i class="fas fa-chevron-right text-xs"></i>
            </a>
        @else
            <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-100 text-gray-300">
                <i class="fas fa-chevron-right text-xs"></i>
            </span>
        @endif
    </div>

    {{-- Zoning Assessments Chart --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Zoning Assessments — {{ $chartYear }}</h3>
        <canvas id="zoningChart" height="90"></canvas>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Zoning Activity --}}
        <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Zoning Activity</h3>
            <div class="space-y-3">
                @forelse($recentZoningActivity as $app)
                <a href="{{ $app->route }}" class="block p-3 rounded-lg hover:bg-gray-50 transition border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono text-gray-500">{{ $app->application_number }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @switch($app->status)
                                @case('for_zoning_assessment') bg-purple-100 text-purple-700 @break
                                @case('paid') bg-green-100 text-green-700 @break
                                @case('released') bg-green-100 text-green-700 @break
                                @default bg-yellow-100 text-yellow-700
                            @endswitch
                        ">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span>
                    </div>
                    <p class="text-sm text-gray-900 mt-1 truncate">{{ $app->applicant_full_name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $app->updated_at->diffForHumans() }}</p>
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">No zoning activity yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const zoningCtx = document.getElementById('zoningChart').getContext('2d');
new Chart(zoningCtx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Zoning Assessments',
            data: @json($zoningChartData),
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
