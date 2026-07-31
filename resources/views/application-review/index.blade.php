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
                    <tr class="hover:bg-gray-50" x-data="{ showDisapprove: false, remarks: '' }">
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

                            <form method="POST" action="{{ route('application-review.approve', ['type' => $app->type, 'id' => $app->id]) }}" class="inline" onsubmit="return confirm('Approve {{ $app->application_number }}? It will be routed into the normal assessment queue.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>

                            @can('reject-applications')
                            <button type="button" @click="showDisapprove = true" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-red-300 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50 transition">
                                <i class="fas fa-times"></i> Disapprove
                            </button>

                            <div x-show="showDisapprove" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @keydown.escape.window="showDisapprove = false">
                                <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 text-left" @click.outside="showDisapprove = false">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="inline-flex items-center justify-center w-10 h-10 bg-red-100 rounded-full">
                                            <i class="fas fa-circle-exclamation text-red-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Disapprove {{ $app->application_number }}</h3>
                                            <p class="text-sm text-gray-500">The application will be returned to the client for revision.</p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('application-review.disapprove', ['type' => $app->type, 'id' => $app->id]) }}" autocomplete="off">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks <span class="text-red-500">*</span></label>
                                            <textarea name="review_remarks" x-model="remarks" required rows="4"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                                placeholder="Explain what the client needs to correct before resubmitting"></textarea>
                                        </div>
                                        <div class="flex items-center justify-end gap-3">
                                            <button type="button" @click="showDisapprove = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Cancel</button>
                                            <button type="submit" :disabled="!remarks" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                                <i class="fas fa-times"></i> Confirm Disapprove
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endcan
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
