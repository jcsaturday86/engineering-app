@extends('layouts.app')

@section('title', 'Audit Logs')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Audit Logs</span>
@endsection

@section('content')
<div class="space-y-4" x-data="{ openRow: null }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Audit Logs</h2>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" action="{{ route('reports.audit-logs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search Description</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. updated, application no."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">User</label>
                <select name="causer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Users</option>
                    @foreach($causers as $causer)
                        <option value="{{ $causer->id }}" @selected(request('causer_id') == $causer->id)>{{ $causer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Record Type</label>
                <select name="subject_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Types</option>
                    @foreach($subjectTypes as $class => $label)
                        <option value="{{ $class }}" @selected(request('subject_type') === $class)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Event</label>
                <select name="event" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Events</option>
                    <option value="created" @selected(request('event') === 'created')>Created</option>
                    <option value="updated" @selected(request('event') === 'updated')>Updated</option>
                    <option value="deleted" @selected(request('event') === 'deleted')>Deleted</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Month</label>
                <input type="month" name="month" value="{{ $month }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="lg:col-span-6 flex gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('reports.audit-logs') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Activity list --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date/Time</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">User</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Event</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Record</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Description</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($activities as $activity)
                    @php
                        $eventColors = [
                            'created' => 'bg-green-100 text-green-700',
                            'updated' => 'bg-blue-100 text-blue-700',
                            'deleted' => 'bg-red-100 text-red-700',
                        ];
                        $subjectLabel = $subjectTypes[$activity->subject_type] ?? class_basename($activity->subject_type ?? 'Unknown');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $activity->created_at->format('M d, Y g:i A') }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $activity->causer->name ?? 'System' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $eventColors[$activity->event] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($activity->event ?? 'n/a') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $subjectLabel }}
                            @if($activity->subject_id)
                                <span class="text-gray-400">#{{ $activity->subject_id }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $activity->description }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(!empty($activity->properties) && $activity->properties->isNotEmpty())
                            <button type="button" @click="openRow = openRow === {{ $activity->id }} ? null : {{ $activity->id }}"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-code"></i> View
                            </button>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($activity->properties) && $activity->properties->isNotEmpty())
                    <tr x-show="openRow === {{ $activity->id }}" x-cloak>
                        <td colspan="6" class="px-4 py-3 bg-gray-50">
                            <pre class="text-xs text-gray-700 whitespace-pre-wrap font-mono">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-clipboard-list text-3xl mb-3"></i>
                            <p>No activity found for the selected filters</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activities->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $activities->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
