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
                    <p class="text-sm text-gray-500">Total Applications</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_applications']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-file-alt text-blue-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">This year</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($stats['pending_applications']) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Awaiting processing</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">For Payment</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($stats['for_payment']) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-orange-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Billed &amp; awaiting payment</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Released</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['approved_applications']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">This year</p>
        </div>
    </div>

    {{-- Revenue Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-5 text-white">
            <p class="text-sm text-blue-100">Annual Revenue</p>
            <p class="text-2xl font-bold mt-1">&#8369;{{ number_format($stats['total_revenue'], 2) }}</p>
            <p class="text-xs text-blue-200 mt-2">{{ now()->year }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-xl p-5 text-white">
            <p class="text-sm text-emerald-100">Monthly Revenue</p>
            <p class="text-2xl font-bold mt-1">&#8369;{{ number_format($stats['monthly_revenue'], 2) }}</p>
            <p class="text-xs text-emerald-200 mt-2">{{ now()->format('F Y') }}</p>
        </div>
        <div class="bg-gradient-to-br from-violet-600 to-violet-700 rounded-xl p-5 text-white">
            <p class="text-sm text-violet-100">Today's Transactions</p>
            <p class="text-2xl font-bold mt-1">{{ number_format($stats['daily_transactions']) }}</p>
            <p class="text-xs text-violet-200 mt-2">{{ now()->format('M d, Y') }}</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Revenue Chart --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Revenue — {{ $chartYear }}</h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>

        {{-- Monthly Transactions Chart --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Transactions — {{ $chartYear }}</h3>
            <canvas id="transactionsChart" height="200"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Applications --}}
        <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Applications</h3>
            <div class="space-y-3">
                @forelse($recentApplications as $app)
                <a href="{{ $app->route }}" class="block p-3 rounded-lg hover:bg-gray-50 transition border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono text-gray-500">{{ $app->application_number }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @switch($app->status)
                                @case('draft') bg-gray-100 text-gray-600 @break
                                @case('submitted') bg-blue-100 text-blue-700 @break
                                @case('for_zoning_assessment') bg-purple-100 text-purple-700 @break
                                @case('paid') bg-green-100 text-green-700 @break
                                @case('cancelled') bg-red-100 text-red-700 @break
                                @default bg-yellow-100 text-yellow-700
                            @endswitch
                        ">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span>
                    </div>
                    <p class="text-sm text-gray-900 mt-1 truncate">{{ $app->applicant_full_name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $app->permit_type_code }} &middot; {{ $app->created_at->diffForHumans() }}</p>
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">No applications yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Revenue (₱)',
            data: @json($revenueData),
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return '₱' + value.toLocaleString(); }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: { grid: { display: false } }
        }
    }
});

const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
new Chart(transactionsCtx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [
            {
                label: 'Building Permit',
                data: @json($bpTransactionData),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Occupancy Permit',
                data: @json($opTransactionData),
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Demolition Permit',
                data: @json($extraTransactionData['dp']),
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Fencing Permit',
                data: @json($extraTransactionData['fp']),
                backgroundColor: 'rgba(249, 115, 22, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Signage Permit',
                data: @json($extraTransactionData['sgp']),
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Annual Inspection',
                data: @json($extraTransactionData['ai']),
                backgroundColor: 'rgba(20, 184, 166, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' },
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
