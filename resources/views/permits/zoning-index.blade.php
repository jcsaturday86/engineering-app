@extends('layouts.app')

@section('title', 'Zoning Documents')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="#" class="text-gray-500 hover:text-gray-700">Permits</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Zoning Documents</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Zoning Documents</h2>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4" autocomplete="off">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, application number, project..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    @foreach(['paid' => 'Paid', 'permit_generated' => 'Permit generated', 'released' => 'Released'] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Year</label>
                <select name="year" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach([now()->year, now()->year - 1] as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'status']) || $year != now()->year)
                <a href="{{ route('permits.zoning') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Application No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Applicant</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Project Title</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    @php
                        $decisionNo = $app->zoningAssessment?->decision_no;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('applications.show', $app) }}" class="font-mono font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $app->application_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                        <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate">{{ $app->project_title ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $app->zoningAssessment?->certificate_date?->format('M d, Y') ?? $app->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(! $decisionNo)
                                @can('generate-permits')
                                <form action="{{ route('permits.generateZoning', $app) }}" method="POST" class="inline" autocomplete="off"
                                    onsubmit="return confirm('Generate zoning documents for this application?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-file-alt"></i> Generate
                                    </button>
                                </form>
                                @endcan
                            @else
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('permits.zoningCert', $app) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-print"></i> Certification
                                    </a>
                                    <a href="{{ route('permits.locational', $app) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-print"></i> Loc. Clearance
                                    </a>
                                    <a href="{{ route('permits.evaluation', $app) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-print"></i> Eval. Report
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-map-marked-alt text-3xl mb-3"></i>
                            <p>No paid applications found for zoning documents</p>
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
