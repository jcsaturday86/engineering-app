@extends('layouts.app')

@section('title', 'Occupancy Permit Applications')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Occupancy Permit Applications</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Occupancy Permit Applications</h2>
        @can('create-applications')
        <a href="{{ route('occupancy-applications.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-plus"></i> New Occupancy Permit
        </a>
        @endcan
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4" autocomplete="off">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, application number..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                @php
                    $statusLabels = [
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'zoning_assessed' => 'For Occupancy Assessment',
                        'engineering_assessed' => 'Engineering Assessed',
                        'billed' => 'Billed',
                        'paid' => 'Paid',
                        'permit_generated' => 'Permit Generated',
                        'released' => 'Released',
                    ];
                @endphp
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">All</option>
                    @foreach($statusLabels as $s => $label)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Year</label>
                <select name="year" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    @foreach([now()->year, now()->year - 1] as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'status']) || $year != now()->year)
                <a href="{{ route('occupancy-applications.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">App No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Applicant</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Project Title</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Turn Around Time</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('occupancy-applications.show', $app) }}" class="font-mono text-indigo-600 hover:text-indigo-800 font-medium">
                                {{ $app->application_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                                {{ $app->applicationType->name ?? 'OP' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                        <td class="px-4 py-3 text-gray-600 max-w-[240px] truncate">{{ $app->project_title ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = [
                                    'draft' => 'bg-gray-100 text-gray-600',
                                    'submitted' => 'bg-blue-100 text-blue-700',
                                    'zoning_assessed' => 'bg-yellow-100 text-yellow-700',
                                    'engineering_assessed' => 'bg-amber-100 text-amber-700',
                                    'billed' => 'bg-orange-100 text-orange-700',
                                    'paid' => 'bg-green-100 text-green-700',
                                    'permit_generated' => 'bg-indigo-100 text-indigo-700',
                                    'released' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                                $labels = [
                                    'draft' => 'Draft',
                                    'submitted' => 'Submitted',
                                    'zoning_assessed' => 'For Occupancy Assessment',
                                    'engineering_assessed' => 'Engineering Assessed',
                                    'billed' => 'Billed',
                                    'paid' => 'Paid',
                                    'permit_generated' => 'Permit Generated',
                                    'released' => 'Released',
                                    'cancelled' => 'Cancelled',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$app->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $labels[$app->status] ?? ucfirst(str_replace('_', ' ', $app->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @php
                                $permit = $app->permits->sortByDesc('created_at')->first();
                                $tatStart = $app->submitted_at ?? $app->created_at;
                                $tatDays = $permit ? (int) floor($tatStart->diffInDays($permit->created_at, true)) : null;
                            @endphp
                            {{ $tatDays !== null ? $tatDays . ' day' . ($tatDays == 1 ? '' : 's') : '–' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('occupancy-applications.show', $app) }}" class="text-gray-400 hover:text-indigo-600" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-folder-open text-3xl mb-3"></i>
                            <p>No occupancy permit applications found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $applications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
