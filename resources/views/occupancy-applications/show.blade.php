@extends('layouts.app')

@section('title', 'OP Application Details')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('occupancy-applications.index') }}" class="text-gray-500 hover:text-gray-700">Occupancy Applications</a>
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
    $isOP = true;
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
                            {{ $application->getPermitTypeCode() }} &mdash; Occupancy Permit
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($application->status === 'draft')
                    <a href="{{ route('occupancy-applications.edit', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('occupancy-applications.submit', $application) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                    </form>
                @endif
                @if(!in_array($application->status, ['cancelled', 'paid', 'released']))
                    <form method="POST" action="{{ route('occupancy-applications.cancel', $application) }}" class="inline" onsubmit="return confirm('Are you sure you want to cancel this application? This action cannot be undone.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                            <i class="fas fa-times-circle"></i> Cancel
                        </button>
                    </form>
                @endif
                <a href="{{ route('occupancy-applications.print', $application) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-print"></i> Print
                </a>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 1. APPLICATION DETAILS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Application Details
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <p class="text-xs text-gray-500">Project Title</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->project_title ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Application Type</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicationType?->name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Date of Completion</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->completion_date ? $application->completion_date->format('M d, Y') : '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 2. APPLICANT INFORMATION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
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
                <p class="text-xs text-gray-500">Suffix</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_suffix ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Contact No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_contact_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Email</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_email ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 3. APPLICANT ADDRESS --}}
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
    {{-- 4. BUILDING LOCATION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Building Location
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Street</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->building_street ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Barangay</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->buildingBarangay?->name ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">City/Municipality</p>
                <p class="text-sm text-gray-900 mt-0.5">City of San Fernando, La Union</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 5. BUILDING SPECS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Building Specs
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">No. of Storey/s</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->no_of_storeys ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">No. of Units</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->no_of_units ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Gross Floor Area</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) . ' sqm' : '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 6. CHARACTER OF OCCUPANCY --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Use or Character of Occupancy
        </h3>
        @if($application->applicationOccupancyGroups && $application->applicationOccupancyGroups->count())
            <div class="space-y-2">
                @foreach($application->applicationOccupancyGroups as $occGroup)
                    <div class="flex items-start gap-2 text-sm">
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium shrink-0 mt-0.5">
                            {{ $loop->iteration }}
                        </span>
                        <div>
                            <span class="text-gray-900 font-medium">{{ $occGroup->occupancyGroup?->name ?? '---' }}</span>
                            @if($occGroup->occupancySubGroup)
                                <span class="text-gray-400 mx-1">&rarr;</span>
                                <span class="text-gray-600">{{ $occGroup->occupancySubGroup->name }}</span>
                            @endif
                            @if($occGroup->others_text)
                                <span class="text-gray-500 italic ml-1">({{ $occGroup->others_text }})</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">No occupancy groups selected.</p>
        @endif
    </div>

    {{-- ================================================================== --}}
    {{-- 7. OCCUPANCY PERMIT DETAILS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Occupancy Permit Details
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Building Permit No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->bp_number ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">BP Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->bp_issued_date ? $application->bp_issued_date->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">FSEC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->fsec_no ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">FSEC Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->fsec_issued_date ? $application->fsec_issued_date->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Applies For (FSIC)</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applies_for ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Date of Completion</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->completion_date ? $application->completion_date->format('M d, Y') : '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 8. OWNER INFORMATION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Owner Information
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
                <p class="text-xs text-gray-500">Date Signed</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_date_signed ? $application->owner_date_signed->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Gov't ID No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_govt_id ?? '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">ID Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_id_date_issued ? $application->owner_id_date_issued->format('M d, Y') : '---' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Place Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_id_place_issued ?? '---' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 9. REMARKS --}}
    {{-- ================================================================== --}}
    @if($application->remarks)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Remarks
        </h3>
        <p class="text-sm text-gray-900 whitespace-pre-line">{{ $application->remarks }}</p>
    </div>
    @endif

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
    {{-- WORKFLOW ACTION BUTTONS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Workflow Actions
        </h3>
        <div class="flex flex-wrap gap-2">
            @if($application->status === 'submitted')
                <a href="{{ route('assessments.assess.op', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition">
                    <i class="fas fa-clipboard-check"></i> Assess
                </a>
            @endif
            @if($application->status === 'engineering_assessed')
                <a href="{{ route('billing.generate.op', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-file-invoice-dollar"></i> Generate Billing
                </a>
            @endif
            @if($application->status === 'billed')
                <a href="{{ route('collections.create.op', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-money-bill-wave"></i> Record Payment
                </a>
            @endif
            @if($application->status === 'paid')
                <a href="{{ route('permits.generate.op', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-certificate"></i> Generate Permit
                </a>
            @endif
            @if(in_array($application->status, ['draft']))
                <span class="text-sm text-gray-500 italic">Submit the application to begin the workflow.</span>
            @endif
            @if(in_array($application->status, ['permit_generated', 'released']))
                <span class="text-sm text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i> Workflow complete.</span>
            @endif
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ACTIVITY LOG --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Activity Log
        </h3>
        @php
            $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($application))
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
