@extends('layouts.app')

@section('title', 'Application Details')

@section('breadcrumbs')
    <a href="{{ route('online.dashboard') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application->application_number }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900 font-mono">{{ $application->application_number }}</h2>
            <p class="text-sm text-gray-500">{{ $application->permitType?->name }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('online.upload', $application) }}" class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                <i class="fas fa-upload mr-1"></i> Upload
            </a>
            <a href="{{ route('online.track', $application) }}" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">
                <i class="fas fa-map-marker-alt mr-1"></i> Track
            </a>
            @if(in_array($application->status, ['permit_generated', 'released']))
            <a href="{{ route('online.download', $application) }}" class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                <i class="fas fa-download mr-1"></i> Download Permit
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Application Details</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</span></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Project</dt><dd class="text-gray-900">{{ $application->project_title ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Scope</dt><dd class="text-gray-900">{{ $application->scopeOfWork?->name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Est. Cost</dt><dd class="text-gray-900">&#8369;{{ number_format($application->total_estimated_cost, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Date Filed</dt><dd class="text-gray-900">{{ $application->created_at->format('M d, Y') }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Requirements ({{ $application->applicationRequirements->count() }})</h3>
            @forelse($application->applicationRequirements as $req)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm text-gray-700">{{ $req->requirement_name }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full
                    @if($req->status === 'approved') bg-green-100 text-green-700
                    @elseif($req->status === 'rejected') bg-red-100 text-red-700
                    @else bg-yellow-100 text-yellow-700 @endif">{{ ucfirst($req->status) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">No requirements uploaded.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
