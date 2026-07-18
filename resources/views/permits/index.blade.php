@extends('layouts.app')

@php
    $typeLabel = match($type) {
        'building' => 'Building Permits',
        'demolition' => 'Demolition Permits',
        'signage' => 'Signage Permits',
        'fencing' => 'Fencing Permits',
        'mechanical' => 'Annual Inspection Permits',
        default => 'Occupancy Permits',
    };
@endphp

@section('title', $typeLabel)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="#" class="text-gray-500 hover:text-gray-700">Permits</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $typeLabel }}</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">{{ $typeLabel }}</h2>
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
                    @foreach(['paid' => 'Paid', 'permit_generated' => 'Permit generated', 'released' => 'Released', 'revoked' => 'Revoked'] as $value => $label)
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
                @php
                    $clearRoute = match($type) {
                        'building' => route('permits.building'),
                        'demolition' => route('permits.demolition'),
                        'signage' => route('permits.signage'),
                        'fencing' => route('permits.fencing'),
                        'mechanical' => route('permits.annualInspection'),
                        default => route('permits.occupancy'),
                    };
                @endphp
                <a href="{{ $clearRoute }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Permit No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Applicant</th>
                        @unless(in_array($type, ['demolition', 'signage', 'fencing', 'mechanical']))
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Project Title</th>
                        @endunless
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">TTA</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    @php
                        $isMechanical = $type === 'mechanical';
                        $revokedPermit = $app->status === 'paid' && $app->permits->isEmpty()
                            ? $app->permits()->onlyTrashed()->where('status', 'revoked')->latest('deleted_at')->first()
                            : null;
                        $wasRevoked = (bool) $revokedPermit;
                        $showRoute = match($type) {
                            'occupancy' => route('occupancy-applications.show', $app),
                            'demolition' => route('demolition-applications.show', $app),
                            'signage' => route('signage-applications.show', $app),
                            'fencing' => route('fencing-applications.show', $app),
                            'mechanical' => route('annual-inspection-applications.show', $app),
                            default => route('applications.show', $app),
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ $showRoute }}" class="font-mono font-medium hover:underline {{ $revokedPermit ? 'text-red-600 line-through' : 'text-blue-600 hover:text-blue-800' }}" @if($revokedPermit) title="Revoked" @endif>
                                {{ $app->permits->first()->permit_number ?? ($revokedPermit->permit_number ?? '-') }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $isMechanical ? $app->owner_name : $app->applicant_last_name . ', ' . $app->applicant_first_name }}</td>
                        @unless(in_array($type, ['demolition', 'signage', 'fencing', 'mechanical']))
                        <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate">{{ $app->project_title ?? '-' }}</td>
                        @endunless
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
                            @if($wasRevoked)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    Permit Revoked
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$app->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @php
                                $tatPermit = $app->permits->first() ?? $revokedPermit;
                                $tatStart = $app->submitted_at ?? $app->created_at;
                                $tatDays = $tatPermit ? (int) floor($tatStart->diffInDays($tatPermit->created_at, true)) : null;
                            @endphp
                            {{ $tatDays !== null ? $tatDays . ' day' . ($tatDays == 1 ? '' : 's') : '–' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($wasRevoked)
                                @can('revert-permits')
                                <div class="inline-flex items-center gap-1.5" x-data="{ showRestoreModal: false, restorePassword: '' }">
                                    <button type="button" @click="showRestoreModal = true; restorePassword = ''"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-history"></i> Restore
                                    </button>

                                    <div x-show="showRestoreModal" x-cloak
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                                        @keydown.escape.window="showRestoreModal = false">
                                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 text-left" @click.outside="showRestoreModal = false">
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="inline-flex items-center justify-center w-10 h-10 bg-blue-100 rounded-full">
                                                    <i class="fas fa-lock text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900">Confirm Restore</h3>
                                                    <p class="text-sm text-gray-500">This will restore the revoked permit (same permit number) and set the application back to Permit Generated.</p>
                                                </div>
                                            </div>

                                            @if($errors->has('password'))
                                                <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                                    {{ $errors->first('password') }}
                                                </div>
                                            @endif

                                            @php
                                                $restoreRoute = match($type) {
                                                    'building' => route('permits.restorePermit', $app),
                                                    'demolition' => route('permits.restorePermit.dp', $app),
                                                    'signage' => route('permits.restorePermit.sgp', $app),
                                                    'fencing' => route('permits.restorePermit.fp', $app),
                                                    'mechanical' => route('permits.restorePermit.ai', $app),
                                                    default => route('permits.restorePermit.op', $app),
                                                };
                                            @endphp
                                            <form action="{{ $restoreRoute }}" method="POST" autocomplete="off">
                                                @csrf
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                                    <input type="password" name="password" x-model="restorePassword" required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Enter your account password">
                                                </div>
                                                <div class="flex items-center justify-end gap-3">
                                                    <button type="button" @click="showRestoreModal = false"
                                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                        Cancel
                                                    </button>
                                                    <button type="submit" :disabled="!restorePassword"
                                                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                                        <i class="fas fa-history"></i> Confirm & Restore
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            @elseif($app->permits->isEmpty())
                                @php
                                    $generateRoute = match($type) {
                                        'building' => route('permits.generate', $app),
                                        'demolition' => route('permits.generate.dp', $app),
                                        'signage' => route('permits.generate.sgp', $app),
                                        'fencing' => route('permits.generate.fp', $app),
                                        'mechanical' => route('permits.generate.ai', $app),
                                        default => route('permits.generate.op', $app),
                                    };
                                @endphp
                                <form action="{{ $generateRoute }}" method="POST" class="inline" autocomplete="off">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-file-alt"></i> Generate
                                    </button>
                                </form>
                            @else
                                <div class="inline-flex items-center gap-1.5" x-data="{ showRevokeModal: false, revokePassword: '', revokeReason: '' }">
                                    @if(!in_array($type, ['demolition', 'fencing']))
                                    <a href="{{ route('permits.print', $app->permits->first()) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-print"></i> Print
                                    </a>
                                    @endif

                                    @can('revert-permits')
                                    @if($app->status === 'permit_generated')
                                        <button type="button" @click="showRevokeModal = true; revokePassword = ''; revokeReason = ''"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-red-300 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50 transition">
                                            <i class="fas fa-undo"></i> Revoke
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

                                                @if($errors->has('password') || $errors->has('reason'))
                                                    <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                                        {{ $errors->first('password') ?: $errors->first('reason') }}
                                                    </div>
                                                @endif

                                                @php
                                                    $revertGenerateRoute = match($type) {
                                                        'building' => route('permits.revertGenerate', $app),
                                                        'demolition' => route('permits.revertGenerate.dp', $app),
                                                        'signage' => route('permits.revertGenerate.sgp', $app),
                                                        'fencing' => route('permits.revertGenerate.fp', $app),
                                                        'mechanical' => route('permits.revertGenerate.ai', $app),
                                                        default => route('permits.revertGenerate.op', $app),
                                                    };
                                                @endphp
                                                <form action="{{ $revertGenerateRoute }}" method="POST" autocomplete="off">
                                                    @csrf
                                                    <div class="mb-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Revoking <span class="text-red-500">*</span></label>
                                                        <textarea name="reason" x-model="revokeReason" required rows="3"
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                                            placeholder="Provide a detailed reason for revoking this permit"></textarea>
                                                    </div>
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
                                                        <button type="submit" :disabled="!revokePassword || !revokeReason"
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
                        <td colspan="{{ in_array($type, ['demolition', 'signage', 'fencing', 'mechanical']) ? 6 : 7 }}" class="px-4 py-12 text-center text-gray-400">
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
