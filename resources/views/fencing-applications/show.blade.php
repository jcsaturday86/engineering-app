@extends('layouts.app')

@section('title', 'FP Application Details')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('fencing-applications.index') }}" class="text-gray-500 hover:text-gray-700">Fencing Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application->application_number }}</span>
@endsection

@section('content')
@php
    $statusEnum = \App\Enums\ApplicationStatus::tryFrom($application->status);
    $sectionNum = 0;
    $portal = $portal ?? 'staff';
    $applicationType = $applicationType ?? 'FP';
    $routeParams = ['type' => $applicationType, 'id' => $application->id];
    $canEdit = in_array($application->status, ['draft', 'returned']);
    $canSubmit = $canEdit && \App\Support\ClientWizard::missingMandatory($application, $applicationType)->isEmpty();
    $canCancel = ! in_array($application->status, ['paid', 'permit_generated', 'released', 'cancelled'], true);
    $isApproved = $application->approved_at !== null;
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
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-700">
                            {{ $application->getPermitTypeCode() }} &mdash; Fencing Permit
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusEnum?->color() ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusEnum?->label() ?? ucfirst(str_replace('_', ' ', $application->status)) }}
                        </span>
                    </div>
                </div>
            </div>
            @if($portal === 'staff')
            <div class="flex flex-wrap items-center gap-2" x-data="{ showRevertSubmitModal: false, revertSubmitPassword: '', showSubmitModal: false, submitPassword: '', showApproveModal: false, approvePassword: '', showDisapprove: false, disapproveRemarks: '', disapprovePassword: '' }">
                @if($application->status === 'draft' && $application->source !== 'online')
                    <a href="{{ route('fencing-applications.edit', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button type="button" @click="showSubmitModal = true; submitPassword = ''"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition">
                        <i class="fas fa-paper-plane"></i> Submit
                    </button>

                    <div x-show="showSubmitModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        @keydown.escape.window="showSubmitModal = false">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showSubmitModal = false">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 bg-teal-100 rounded-full">
                                    <i class="fas fa-lock text-teal-600"></i>
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

                            <form method="POST" action="{{ route('fencing-applications.submit', $application) }}" autocomplete="off">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" x-model="submitPassword" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Enter your account password">
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showSubmitModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="!submitPassword"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-paper-plane"></i> Confirm & Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
                @if($application->status === 'pending_approval')
                    @can('approve-applications')
                    <button type="button" @click="showApproveModal = true; approvePassword = ''"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-check"></i> Approve
                    </button>

                    <div x-show="showApproveModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        @keydown.escape.window="showApproveModal = false">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showApproveModal = false">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 bg-green-100 rounded-full">
                                    <i class="fas fa-lock text-green-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Confirm Approval</h3>
                                    <p class="text-sm text-gray-500">Enter your password to approve {{ $application->application_number }}. It will be routed into the normal assessment queue.</p>
                                </div>
                            </div>

                            @if($errors->has('password'))
                                <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('application-review.approve', ['type' => 'FP', 'id' => $application->id]) }}" autocomplete="off">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" x-model="approvePassword" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder="Enter your account password">
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showApproveModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="!approvePassword"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-check"></i> Confirm & Approve
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endcan
                    @can('reject-applications')
                    <button type="button" @click="showDisapprove = true" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                        <i class="fas fa-times"></i> Disapprove
                    </button>

                    <div x-show="showDisapprove" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @keydown.escape.window="showDisapprove = false">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showDisapprove = false">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 bg-red-100 rounded-full">
                                    <i class="fas fa-circle-exclamation text-red-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Disapprove {{ $application->application_number }}</h3>
                                    <p class="text-sm text-gray-500">The application will be returned to the client for revision.</p>
                                </div>
                            </div>

                            @if($errors->has('review_remarks'))
                                <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                    {{ $errors->first('review_remarks') }}
                                </div>
                            @endif
                            @if($errors->has('password'))
                                <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('application-review.disapprove', ['type' => 'FP', 'id' => $application->id]) }}" autocomplete="off">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks <span class="text-red-500">*</span></label>
                                    <textarea name="review_remarks" x-model="disapproveRemarks" required rows="4"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                        placeholder="Explain what the client needs to correct before resubmitting"></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" x-model="disapprovePassword" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                        placeholder="Enter your account password">
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showDisapprove = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Cancel</button>
                                    <button type="submit" :disabled="!disapproveRemarks || !disapprovePassword" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-times"></i> Confirm Disapprove
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endcan
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

                            <form action="{{ route('fencing-applications.revertSubmission', $application) }}" method="POST" autocomplete="off">
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
            @else
            <div class="flex gap-1.5 flex-wrap items-center" x-data="{ showCancelModal: false, cancelPassword: '' }">
                @if($application->status === 'billed')
                <a href="{{ route('online.pay', $routeParams) }}" class="px-2.5 py-1.5 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700">
                    <i class="fas fa-credit-card mr-1"></i> Pay Online
                </a>
                @endif
                @if(in_array($application->status, ['billed', 'paid', 'permit_generated', 'released']))
                <a href="{{ route('online.printAssessment', $routeParams) }}" target="_blank" class="px-2.5 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                    <i class="fas fa-print mr-1"></i> Print Summary of Computation
                </a>
                @endif
                <a href="{{ route('online.print', $routeParams) }}" class="px-2.5 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700" target="_blank">
                    <i class="fas fa-print mr-1"></i> Print Application Form
                </a>
                @if($canEdit)
                <a href="{{ route('online.upload', $routeParams) }}" class="px-2.5 py-1.5 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-upload mr-1"></i> Upload Requirements
                </a>
                <form method="POST" action="{{ route('online.submit', $routeParams) }}" class="inline" onsubmit="return confirm('Submit this application for Engineering review? You won\'t be able to edit it until it is reviewed.');">
                    @csrf
                    <button type="submit" @disabled(!$canSubmit) title="{{ $canSubmit ? '' : 'Upload all mandatory documents before submitting' }}"
                        class="px-2.5 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600">
                        <i class="fas fa-paper-plane mr-1"></i> Submit for Review
                    </button>
                </form>
                <a href="{{ route('online.edit', $routeParams) }}" class="px-2.5 py-1.5 bg-amber-600 text-white text-xs rounded-lg hover:bg-amber-700">
                    <i class="fas fa-pen mr-1"></i> Edit
                </a>
                @endif
                @if($canCancel)
                <button type="button" @click="showCancelModal = true; cancelPassword = ''" class="px-2.5 py-1.5 bg-white border border-red-300 text-red-600 text-xs rounded-lg hover:bg-red-50">
                    <i class="fas fa-ban mr-1"></i> Cancel Application
                </button>

                <div x-show="showCancelModal" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    @keydown.escape.window="showCancelModal = false">
                    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showCancelModal = false">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="inline-flex items-center justify-center w-10 h-10 bg-red-100 rounded-full">
                                <i class="fas fa-lock text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Cancel Application</h3>
                                <p class="text-sm text-gray-500">This action cannot be undone. Your application will be marked as cancelled and any uploaded documents will be permanently removed.</p>
                            </div>
                        </div>

                        @if($errors->has('password'))
                            <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                {{ $errors->first('password') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('online.destroy', $routeParams) }}" autocomplete="off">
                            @csrf
                            @method('DELETE')
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password" x-model="cancelPassword" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Enter your account password">
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="showCancelModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    Back
                                </button>
                                <button type="submit" :disabled="!cancelPassword"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-ban"></i> Confirm & Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                @if(in_array($application->status, ['permit_generated', 'released']))
                <a href="{{ route('online.download', $routeParams) }}" target="_blank" onclick="setTimeout(() => window.location.reload(), 1200)" class="px-2.5 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">
                    <i class="fas fa-download mr-1"></i> Generate Permit
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>

    @if(($portal ?? 'staff') === 'client' && in_array($application->status, ['draft', 'returned'], true))
        @include('partials.client-wizard-stepper', [
            'wizardStep' => \App\Support\ClientWizard::currentStep($application, $applicationType),
        ])
    @endif

    @include('partials.application-stepper')

    @if($application->status === 'returned' && $application->review_remarks)
    <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
        <i class="fas fa-circle-exclamation text-red-500 mt-0.5"></i>
        <div>
            <p class="text-sm font-semibold text-red-800">Returned for revision by Engineering</p>
            <p class="text-sm text-red-700 mt-1">{{ $application->review_remarks }}</p>
            <p class="text-xs text-red-600 mt-1">Edit your application to address the notes above, then submit it again for review.</p>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        @include('partials.requirements-uploaded-list')
    </div>

    {{-- ================================================================== --}}
    {{-- 1. APPLICANT INFORMATION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
            <div>
                <p class="text-xs text-gray-500">TIN</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_tin ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Telephone</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_telephone ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Owned By Enterprise</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owned_by_enterprise ? ($application->enterprise_name ?? 'Yes') : 'No' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Form of Ownership</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->formOfOwnership?->name ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 2. APPLICANT ADDRESS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
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
            <div>
                <p class="text-xs text-gray-500">CTC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_ctc_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">CTC Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_ctc_date_issued ? $application->applicant_ctc_date_issued->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">CTC Issued At</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_ctc_issued_at ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 3. LOCATION OF CONSTRUCTION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Location of Construction
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Lot No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->lot_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Blk No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->block_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">TCT No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->tct_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Tax Dec. No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->tax_dec_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Street</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->construction_street ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Barangay</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->constructionBarangay?->name ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 4. SCOPE OF WORK --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work
        </h3>
        @php
            $scopeLabels = [
                'new_construction' => 'New Construction',
                'erection' => 'Erection',
                'addition' => 'Addition',
                'repair' => 'Repair',
                'others' => 'Others',
            ];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Scope</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $scopeLabels[$application->scope_of_work] ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Details</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->scope_of_work_detail ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 5. DESIGN PROFESSIONAL, PLANS AND SPECIFICATIONS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Design Professional, Plans and Specifications
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Address</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_address ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PRC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_prc_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Validity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_prc_validity ? $application->design_professional_prc_validity->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_ptr_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_ptr_date_issued ? $application->design_professional_ptr_date_issued->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Issued At</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_ptr_issued_at ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">TIN</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->design_professional_tin ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 6. FULL-TIME INSPECTOR OR SUPERVISOR --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Full-Time Inspector or Supervisor
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Address</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_address ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PRC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_prc_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Validity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_prc_validity ? $application->inspector_prc_validity->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_ptr_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_ptr_date_issued ? $application->inspector_ptr_date_issued->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Issued At</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_ptr_issued_at ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">TIN</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->inspector_tin ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 7. CONSENT OF LOT OWNER --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Consent of Lot Owner
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Address</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_address ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">CTC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_ctc_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">CTC Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_ctc_date_issued ? $application->owner_ctc_date_issued->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Issued At</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_ctc_issued_at ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ASSESSMENT SUMMARY --}}
    {{-- ================================================================== --}}
    @if($portal === 'staff' && $application->assessments && $application->assessments->count())
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Assessment Summary
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
                <span class="text-lg font-bold text-teal-700">&#8369;{{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

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
