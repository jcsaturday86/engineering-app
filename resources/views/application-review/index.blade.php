@extends('layouts.app')

@section('title', 'Application Review')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Application Review</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Application Review</h2>
            <p class="text-sm text-gray-500 mt-1">Online submissions awaiting Engineering approval. Approve routes an application into the normal assessment queue; disapprove returns it to the client with your remarks.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('application-review.index') }}" class="px-3 py-1.5 text-xs font-medium rounded-full border {{ $filterType === '' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300' }}">All</a>
            @foreach($permitTypes as $code => $meta)
            <a href="{{ route('application-review.index', ['type' => $code]) }}" class="px-3 py-1.5 text-xs font-medium rounded-full border {{ $filterType === $code ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300' }}">{{ $code }}</a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
    <div class="px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">App No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Applicant / Owner</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Submitted</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-blue-600">
                            <a href="{{ route($app->show_route, $app->id) }}" class="hover:underline">{{ $app->application_number }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">{{ $app->type }}</span>
                            <span class="text-gray-500 ml-1">{{ $app->type_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $app->applicant_full_name ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->submitted_at?->format('M d, Y h:i A') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route($app->show_route, $app->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-3"></i>
                            <p>No applications awaiting approval.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
