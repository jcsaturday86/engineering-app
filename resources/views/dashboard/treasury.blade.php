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
                    <p class="text-sm text-gray-500">Today's Transactions</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['daily_transactions']) }}</p>
                </div>
                <div class="w-12 h-12 bg-violet-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-receipt text-violet-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ now()->format('M d, Y') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Today's Revenue</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">&#8369;{{ number_format($stats['daily_revenue'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cash-register text-blue-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ now()->format('M d, Y') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Monthly Revenue</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">&#8369;{{ number_format($stats['monthly_revenue'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-emerald-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ now()->format('F Y') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Annual Revenue</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">&#8369;{{ number_format($stats['annual_revenue'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ now()->year }}</p>
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

    {{-- Revenue Chart --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Revenue — {{ $chartYear }}</h3>
        <canvas id="revenueChart" height="90"></canvas>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Collections --}}
        <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Collections</h3>
            <div class="space-y-3">
                @forelse($recentCollections as $collection)
                <a href="{{ $collection->route }}" target="_blank" class="block p-3 rounded-lg hover:bg-gray-50 transition border border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono text-gray-500">{{ $collection->or_number }}</span>
                        <span class="text-sm font-semibold text-gray-900">&#8369;{{ number_format($collection->amount_received, 2) }}</span>
                    </div>
                    <p class="text-sm text-gray-900 mt-1 truncate">{{ $collection->paid_by }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $collection->application_number }} &middot; {{ ucfirst($collection->payment_mode) }} &middot; {{ $collection->created_at->diffForHumans() }}</p>
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">No collections yet</p>
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
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
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
</script>
@endpush
