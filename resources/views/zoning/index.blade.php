@extends('layouts.app')

@section('title', 'Zoning Assessment')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Zoning</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Zoning Assessment</h2>
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
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Building Location</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('zoning.assess', $app) }}" class="font-mono text-blue-600 hover:text-blue-800 font-medium">
                                {{ $app->application_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                        <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate">{{ $app->project_title ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $app->building_street ?? '' }}{{ $app->building_street && $app->buildingBarangay ? ', ' : '' }}{{ $app->buildingBarangay->name ?? '' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $colors = [
                                    'draft' => 'bg-gray-100 text-gray-600',
                                    'submitted' => 'bg-blue-100 text-blue-700',
                                    'for_zoning_assessment' => 'bg-purple-100 text-purple-700',
                                    'zoning_assessed' => 'bg-yellow-100 text-yellow-700',
                                    'engineering_assessed' => 'bg-amber-100 text-amber-700',
                                    'billed' => 'bg-orange-100 text-orange-700',
                                    'paid' => 'bg-green-100 text-green-700',
                                    'permit_generated' => 'bg-indigo-100 text-indigo-700',
                                    'released' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$app->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('zoning.assess', $app) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-map-marked-alt"></i> Assess
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-map text-3xl mb-3"></i>
                            <p>No applications pending zoning assessment</p>
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
