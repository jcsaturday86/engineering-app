@extends('layouts.app')

@section('title', 'My Applications')

@section('breadcrumbs')
    <span class="text-gray-900 font-medium">My Applications</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Welcome, {{ auth()->user()->first_name }}!</h2>
        <a href="{{ route('online.apply') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus"></i> New Application
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Applications</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">App No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Project</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-blue-600">{{ $app->application_number }}</td>
                        <td class="px-4 py-3">{{ $app->permit_type_name }}</td>
                        <td class="px-4 py-3 text-gray-600 truncate max-w-[200px]">{{ $app->project_title ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @if($app->status === 'paid' || $app->status === 'permit_generated' || $app->status === 'released') bg-green-100 text-green-700
                                @elseif($app->status === 'cancelled') bg-red-100 text-red-700
                                @else bg-yellow-100 text-yellow-700 @endif">
                                {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @php
                                $showRoute = $app->type === 'op' ? 'online.show.op' : 'online.show';
                                $uploadRoute = $app->type === 'op' ? 'online.upload.op' : 'online.upload';
                                $trackRoute = $app->type === 'op' ? 'online.track.op' : 'online.track';
                                $downloadRoute = $app->type === 'op' ? 'online.download.op' : 'online.download';
                            @endphp
                            <a href="{{ route($showRoute, $app->id) }}" class="text-blue-600 hover:text-blue-800 text-xs">View</a>
                            <a href="{{ route($uploadRoute, $app->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs">Upload</a>
                            <a href="{{ route($trackRoute, $app->id) }}" class="text-gray-600 hover:text-gray-800 text-xs">Track</a>
                            @if(in_array($app->status, ['permit_generated', 'released']))
                            <a href="{{ route($downloadRoute, $app->id) }}" class="text-green-600 hover:text-green-800 text-xs">Download</a>
                            @endif
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
