@extends('layouts.app')

@section('title', 'My Dashboard')

@section('breadcrumbs')
    <span class="text-gray-900 font-medium">My Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Welcome, {{ auth()->user()->first_name }}!</h2>
        <a href="{{ route('online.apply') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus"></i> New Application
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Applications</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Returned for Revision</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['returned'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Recent Applications</h3>
            <a href="{{ route('online.applications') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800">
                View All Applications <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">App No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Project / Owner</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentApplications as $app)
                    @php
                        $statusEnum = \App\Enums\ApplicationStatus::tryFrom($app->status);
                        $routeParams = ['type' => $app->permit_type_code, 'id' => $app->id];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-blue-600">{{ $app->application_number }}</td>
                        <td class="px-4 py-3">{{ $app->permit_type_name }}</td>
                        <td class="px-4 py-3 text-gray-600 truncate max-w-[200px]">{{ $app->project_title ?: $app->applicant_full_name ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusEnum?->color() ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $statusEnum?->label() ?? ucfirst(str_replace('_', ' ', $app->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('online.show', $routeParams) }}" title="View"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-folder-open text-3xl mb-3"></i>
                            <p>No applications yet. <a href="{{ route('online.apply') }}" class="text-blue-600">Apply now</a></p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
