@extends('layouts.app')

@section('title', 'SGP Application Details')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('signage-applications.index') }}" class="text-gray-500 hover:text-gray-700">Signage Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application->application_number }}</span>
@endsection

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-600',
        'submitted' => 'bg-blue-100 text-blue-700',
        'engineering_assessed' => 'bg-amber-100 text-amber-700',
        'billed' => 'bg-orange-100 text-orange-700',
        'paid' => 'bg-green-100 text-green-700',
        'permit_generated' => 'bg-indigo-100 text-indigo-700',
        'released' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
    $sectionNum = 0;
@endphp

<div class="space-y-4">
    {{-- ================================================================== --}}
    {{-- HEADER --}}
    {{-- ================================================================== --}}
    <div class="bg-gray-50 rounded-xl border border-gray-200 px-5 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 font-mono">{{ $application->application_number }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                            {{ $application->getPermitTypeCode() }} &mdash; Signage Permit
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2" x-data="{ showRevertSubmitModal: false, revertSubmitPassword: '', showSubmitModal: false, submitPassword: '' }">
                @if($application->status === 'draft')
                    <a href="{{ route('signage-applications.edit', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button type="button" @click="showSubmitModal = true; submitPassword = ''"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-paper-plane"></i> Submit
                    </button>

                    <div x-show="showSubmitModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        @keydown.escape.window="showSubmitModal = false">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showSubmitModal = false">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 bg-indigo-100 rounded-full">
                                    <i class="fas fa-lock text-indigo-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Confirm Submission</h3>
                                    <p class="text-sm text-gray-500">Enter your password to submit this application.</p>
                                </div>
                            </div>

                            @if($errors->has('password'))
                                <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('signage-applications.submit', $application) }}" autocomplete="off">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" x-model="submitPassword" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Enter your account password">
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showSubmitModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="!submitPassword"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-paper-plane"></i> Confirm & Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
                @if(!in_array($application->status, ['cancelled', 'paid', 'released', 'permit_generated']))
                    <form method="POST" action="{{ route('signage-applications.cancel', $application) }}" class="inline" onsubmit="return confirm('Are you sure you want to cancel this application? This action cannot be undone.')" autocomplete="off">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                            <i class="fas fa-times-circle"></i> Cancel
                        </button>
                    </form>
                @endif
                @can('revert-submission')
                @if($application->status === 'submitted' && !$application->assessments()->where('status', 'finalized')->exists())
                    <button type="button" @click="showRevertSubmitModal = true; revertSubmitPassword = ''"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-amber-300 text-amber-700 text-sm font-medium rounded-lg hover:bg-amber-50 transition">
                        <i class="fas fa-undo"></i> Revert Submission
                    </button>

                    <div x-show="showRevertSubmitModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        @keydown.escape.window="showRevertSubmitModal = false">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showRevertSubmitModal = false">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 bg-amber-100 rounded-full">
                                    <i class="fas fa-lock text-amber-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Confirm Revert</h3>
                                    <p class="text-sm text-gray-500">This will send the application back to draft.</p>
                                </div>
                            </div>

                            @if($errors->has('password'))
                                <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif

                            <form action="{{ route('signage-applications.revertSubmission', $application) }}" method="POST" autocomplete="off">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" x-model="revertSubmitPassword" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        placeholder="Enter your account password">
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showRevertSubmitModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="!revertSubmitPassword"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-undo"></i> Confirm & Revert
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 1. APPLICANT INFORMATION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">First Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_first_name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Middle Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_middle_name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Last Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_last_name ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 2. APPLICANT ADDRESS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Province</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicantProvince?->name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">City/Municipality</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicantCity?->name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Barangay</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicantBarangay?->name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">No./Street/Bldg</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_street ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Zip Code</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_zip_code ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 3. SCOPE OF WORK --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work
        </h3>
        <div class="space-y-3">
            <div>
                <p class="text-xs text-gray-500">a. Install</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->install ? ($application->install_detail ?: 'Yes') : 'No' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">b. Attach</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->attach ? ($application->attach_detail ?: 'Yes') : 'No' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">c. Paint</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->paint ? ($application->paint_detail ?: 'Yes') : 'No' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 4. WORDINGS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Wordings
        </h3>
        <p class="text-sm text-gray-900 whitespace-pre-line">{{ $application->wordings ?: '---' }}</p>
    </div>

    {{-- ================================================================== --}}
    {{-- 5. PREMISES OF --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Premises of
        </h3>
        <p class="text-sm text-gray-900">{{ $application->premises_of ?: '---' }}</p>
    </div>

    {{-- ================================================================== --}}
    {{-- ASSESSMENT SUMMARY --}}
    {{-- ================================================================== --}}
    @if($application->assessments && $application->assessments->count())
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Assessment Summary
        </h3>
        <div class="space-y-4">
            @php $grandTotal = 0; @endphp
            @foreach($application->assessments as $assessment)
                <div class="border border-gray-100 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-800">Assessment #{{ $loop->iteration }}</h4>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Total Amount</p>
                            <p class="text-gray-900 mt-0.5">&#8369;{{ number_format($assessment->total_amount ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Filing Fee</p>
                            <p class="text-gray-900 mt-0.5">&#8369;{{ number_format($assessment->filing_fee ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Processing Fee</p>
                            <p class="text-gray-900 mt-0.5">&#8369;{{ number_format($assessment->processing_fee ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
                @php $grandTotal += ($assessment->total_amount ?? 0) + ($assessment->filing_fee ?? 0) + ($assessment->processing_fee ?? 0); @endphp
            @endforeach
            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                <span class="text-sm font-semibold text-gray-900">Grand Total</span>
                <span class="text-lg font-bold text-indigo-700">&#8369;{{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- ACTIVITY LOG --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Activity Log
        </h3>
        @php
            $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', $application->getMorphClass())
                ->where('subject_id', $application->id)
                ->latest()
                ->take(20)
                ->get();
        @endphp
        @if($activities->count())
            <div class="space-y-3">
                @foreach($activities as $activity)
                    <div class="flex items-start gap-3">
                        <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full shrink-0 mt-0.5">
                            <i class="fas fa-circle-dot text-xs text-gray-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $activity->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">No activity recorded.</p>
        @endif
    </div>

    {{-- Cancelled notice --}}
    @if($application->status === 'cancelled')
    <div class="p-4 bg-red-50 rounded-xl border border-red-200">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-ban text-red-500"></i>
            <span class="text-sm font-semibold text-red-700">Application Cancelled</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-red-600">Cancelled At</p>
                <p class="text-sm text-red-700 mt-0.5">{{ $application->cancelled_at ? $application->cancelled_at->format('M d, Y h:i A') : '---' }}</p>
            </div>
            @if($application->cancellation_reason)
            <div>
                <p class="text-xs text-red-600">Reason</p>
                <p class="text-sm text-red-700 mt-0.5">{{ $application->cancellation_reason }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
