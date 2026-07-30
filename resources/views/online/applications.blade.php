@extends('layouts.app')

@section('title', 'My Applications')

@section('breadcrumbs')
    <a href="{{ route('online.dashboard') }}" class="text-gray-500 hover:text-gray-700">My Dashboard</a>
    <span class="mx-2 text-gray-400">/</span>
    <span class="text-gray-900 font-medium">My Applications</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">My Applications</h2>
        <a href="{{ route('online.apply') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus"></i> New Application
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    @foreach($statusOptions as $s)
                        @php $sEnum = \App\Enums\ApplicationStatus::tryFrom($s); @endphp
                        <option value="{{ $s }}" {{ $statusFilter === $s ? 'selected' : '' }}>{{ $sEnum?->label() ?? ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            @if($statusFilter)
            <a href="{{ route('online.applications') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
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
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Project / Owner</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">TAT</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
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
                            @if($app->status === 'returned' && $app->review_remarks)
                            <p class="text-xs text-red-600 mt-1 max-w-[220px] truncate" title="{{ $app->review_remarks }}">{{ $app->review_remarks }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $app->tat_days !== null ? $app->tat_days . ' day' . ($app->tat_days == 1 ? '' : 's') : '–' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('online.show', $routeParams) }}" title="View"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(in_array($app->status, ['draft', 'returned']))
                                <a href="{{ route('online.edit', $routeParams) }}" title="Edit &amp; Resubmit"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-amber-200 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="{{ route('online.upload', $routeParams) }}" title="Upload Requirements"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-indigo-200 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                    <i class="fas fa-upload"></i>
                                </a>
                                @else
                                <a href="{{ route('online.upload', $routeParams) }}" title="Upload Requirements"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-indigo-200 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                    <i class="fas fa-upload"></i>
                                </a>
                                <a href="{{ route('online.track', $routeParams) }}" title="Track Status"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                    <i class="fas fa-route"></i>
                                </a>
                                @endif
                                @if(in_array($app->status, ['permit_generated', 'released']))
                                <a href="{{ route('online.download', $routeParams) }}" title="Download Permit"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-green-200 text-xs font-medium text-green-700 hover:bg-green-50">
                                    <i class="fas fa-download"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-folder-open text-3xl mb-3"></i>
                            @if($statusFilter)
                            <p>No applications with this status. <a href="{{ route('online.applications') }}" class="text-blue-600">Clear filter</a></p>
                            @else
                            <p>No applications yet. <a href="{{ route('online.apply') }}" class="text-blue-600">Apply now</a></p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
