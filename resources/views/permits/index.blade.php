@extends('layouts.app')

@section('title', $type === 'building' ? 'Building Permits' : 'Occupancy Permits')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="#" class="text-gray-500 hover:text-gray-700">Permits</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $type === 'building' ? 'Building Permits' : 'Occupancy Permits' }}</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">{{ $type === 'building' ? 'Building Permits' : 'Occupancy Permits' }}</h2>
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
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Permit No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('applications.show', $app) }}" class="font-mono text-blue-600 hover:text-blue-800 font-medium">
                                {{ $app->application_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                        <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate">{{ $app->project_title ?? '-' }}</td>
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
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$app->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($app->permits->isNotEmpty())
                                <span class="font-mono text-sm">{{ $app->permits->first()->permit_number }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($app->permits->isEmpty())
                                <form action="{{ $type === 'building' ? route('permits.generate', $app) : route('permits.generate.op', $app) }}" method="POST" class="inline" autocomplete="off">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-file-alt"></i> Generate Permit
                                    </button>
                                </form>
                            @else
                                <div class="inline-flex items-center gap-1.5" x-data="{ showRevokeModal: false, revokePassword: '' }">
                                    <a href="{{ route('permits.print', $app->permits->first()) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-print"></i> Print Permit
                                    </a>

                                    @can('revert-permits')
                                    @if($app->status === 'permit_generated')
                                        <button type="button" @click="showRevokeModal = true; revokePassword = ''"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-red-300 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50 transition">
                                            <i class="fas fa-undo"></i> Revoke Permit
                                        </button>

                                        <div x-show="showRevokeModal" x-cloak
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                                            @keydown.escape.window="showRevokeModal = false">
                                            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 text-left" @click.outside="showRevokeModal = false">
                                                <div class="flex items-center gap-3 mb-4">
                                                    <div class="inline-flex items-center justify-center w-10 h-10 bg-red-100 rounded-full">
                                                        <i class="fas fa-lock text-red-600"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-gray-900">Confirm Revoke</h3>
                                                        <p class="text-sm text-gray-500">This will delete the generated permit and revert to Paid.</p>
                                                    </div>
                                                </div>

                                                @if($errors->has('password'))
                                                    <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                                        {{ $errors->first('password') }}
                                                    </div>
                                                @endif

                                                <form action="{{ $type === 'building' ? route('permits.revertGenerate', $app) : route('permits.revertGenerate.op', $app) }}" method="POST" autocomplete="off">
                                                    @csrf
                                                    <div class="mb-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                                        <input type="password" name="password" x-model="revokePassword" required
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                                            placeholder="Enter your account password">
                                                    </div>
                                                    <div class="flex items-center justify-end gap-3">
                                                        <button type="button" @click="showRevokeModal = false"
                                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" :disabled="!revokePassword"
                                                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                                            <i class="fas fa-undo"></i> Confirm & Revoke
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                    @endcan
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-file-invoice text-3xl mb-3"></i>
                            <p>No paid applications found for permit generation</p>
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
