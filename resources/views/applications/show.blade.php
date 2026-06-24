@extends('layouts.app')

@section('title', 'Application Details')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('applications.index') }}" class="text-gray-500 hover:text-gray-700">Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application->application_number }}</span>
@endsection

@section('content')
@php
    $statusColors = [
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
    $permitColor = $application->permitType->code === 'BP' ? 'bg-blue-100 text-blue-700' : 'bg-indigo-100 text-indigo-700';
    $isBP = $application->permitType->code === 'BP';
    $isOP = $application->permitType->code === 'OP';
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
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium {{ $permitColor }}">
                            {{ $application->permitType->code }} &mdash; {{ $application->permitType->name }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($application->status === 'draft')
                    <a href="{{ route('applications.edit', $application) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('applications.submit', $application) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                    </form>
                @endif
                @if(!in_array($application->status, ['cancelled', 'paid', 'released']))
                    <form method="POST" action="{{ route('applications.cancel', $application) }}" class="inline" onsubmit="return confirm('Are you sure you want to cancel this application? This action cannot be undone.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                            <i class="fas fa-times-circle"></i> Cancel
                        </button>
                    </form>
                @endif
                <a href="{{ route('applications.print', $application) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
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
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Application Details
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <p class="text-xs text-gray-500">Project Title</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->project_title ?? '—' }}</p>
            </div>
            @if($isBP)
            <div>
                <p class="text-xs text-gray-500">Complexity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->complexity ?? '—' }}</p>
            </div>
            @endif
            <div>
                <p class="text-xs text-gray-500">Application Type</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicationType?->name ?? '—' }}</p>
            </div>
            @if($isBP)
            <div>
                <p class="text-xs text-gray-500">Skip Locational Clearance</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applies_to === 'SKIP_LC' ? 'Yes' : 'No' }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 2. APPLICANT INFORMATION --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">First Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_first_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Middle Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_middle_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Last Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_last_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Suffix</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_suffix ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">TIN</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_tin ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Contact No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_contact_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Email</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_email ?? '—' }}</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-100">
            <div>
                <p class="text-xs text-gray-500">Enterprise Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->enterprise_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Form of Ownership</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->formOfOwnership?->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 3. APPLICANT ADDRESS --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Province</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicantProvince?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">City/Municipality</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicantCity?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Barangay</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicantBarangay?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">No./Street/Bldg</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_street ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Zip Code</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_zip_code ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 4. LOCATION OF CONSTRUCTION (BP only) --}}
    {{-- ================================================================== --}}
    @if($isBP)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Location of Construction
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Lot No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->lot_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Blk No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->block_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">TCT No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->tct_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Tax Dec. No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->tax_dec_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Land Classification</p>
                <p class="text-sm text-gray-900 mt-0.5">
                    @if($application->landClassification)
                        {{ $application->landClassification->name }} ({{ $application->landClassification->code }})
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Street</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->building_street ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Barangay</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->buildingBarangay?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">City/Municipality</p>
                <p class="text-sm text-gray-900 mt-0.5">City of San Fernando, La Union</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 5. SCOPE OF WORK (BP only) --}}
    {{-- ================================================================== --}}
    @if($isBP)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Scope of Work</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->scopeOfWork?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Details</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->scope_of_work_details ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 6. CHARACTER OF OCCUPANCY --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Character of Occupancy
        </h3>
        @if($application->applicationOccupancyGroups && $application->applicationOccupancyGroups->count())
            <div class="space-y-2">
                @foreach($application->applicationOccupancyGroups as $occGroup)
                    <div class="flex items-start gap-2 text-sm">
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-700 rounded-full text-xs font-medium shrink-0 mt-0.5">
                            {{ $loop->iteration }}
                        </span>
                        <div>
                            <span class="text-gray-900 font-medium">{{ $occGroup->occupancyGroup?->name ?? '—' }}</span>
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
    {{-- 7. BUILDING DETAILS & COST --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Building Details & Cost
        </h3>

        {{-- Building details grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div>
                <p class="text-xs text-gray-500">Occupancy Classified</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->occupancy_classified ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">No. of Units</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->no_of_units ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">No. of Storeys</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->no_of_storeys ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Floor Area</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) . ' sqm' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Lot Area</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->lot_area ? number_format($application->lot_area, 2) . ' sqm' : '—' }}</p>
            </div>
        </div>

        {{-- Cost table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 pr-4 font-medium text-gray-500">Cost Item</th>
                        <th class="text-right py-2 pl-4 font-medium text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Building Cost</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->building_cost ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Electrical Cost</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->electrical_cost ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Mechanical Cost</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->mechanical_cost ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Electronics Cost</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->electronics_cost ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Plumbing Cost</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->plumbing_cost ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Equipment/Labor Cost 1</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->equipment_cost_1 ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Equipment/Labor Cost 2</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->equipment_cost_2 ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Equipment/Labor Cost 3</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->equipment_cost_3 ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-gray-700">Equipment/Labor Cost 4</td>
                        <td class="py-2 pl-4 text-right text-gray-900">&#8369;{{ number_format($application->equipment_cost_4 ?? 0, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300">
                        <td class="py-2 pr-4 font-bold text-blue-700">Total Estimated Cost</td>
                        <td class="py-2 pl-4 text-right font-bold text-blue-700">&#8369;{{ number_format($application->total_estimated_cost ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Dates and Remarks --}}
        @if($isBP)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5 pt-4 border-t border-gray-100">
            <div>
                <p class="text-xs text-gray-500">Proposed Date of Construction</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->proposed_construction_date ? $application->proposed_construction_date->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Expected Date of Completion</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->expected_completion_date ? $application->expected_completion_date->format('M d, Y') : '—' }}</p>
            </div>
        </div>
        @endif

        @if($application->remarks)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">Remarks</p>
            <p class="text-sm text-gray-900 mt-0.5 whitespace-pre-line">{{ $application->remarks }}</p>
        </div>
        @endif
    </div>

    {{-- ================================================================== --}}
    {{-- 8. OCCUPANCY PERMIT DETAILS (OP only) --}}
    {{-- ================================================================== --}}
    @if($isOP)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Occupancy Permit Details
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Building Permit No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->bp_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">BP Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->bp_issued_date ? $application->bp_issued_date->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">FSEC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->fsec_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">FSEC Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->fsec_issued_date ? $application->fsec_issued_date->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Applies For (FSIC)</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applies_for ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Date of Completion</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->completion_date ? $application->completion_date->format('M d, Y') : '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 9. FULL-TIME INSPECTOR & SUPERVISOR (BP only) --}}
    {{-- ================================================================== --}}
    @if($isBP)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Full-time Inspector & Supervisor
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Date Signed</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_date_signed ? $application->engineer_date_signed->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Address</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_address ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PRC No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_prc_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PRC Validity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_prc_validity ? $application->engineer_prc_validity->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_ptr_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_ptr_date_issued ? $application->engineer_ptr_date_issued->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">PTR Issued At</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_ptr_issued_at ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">TIN</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->engineer_tin ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 10. APPLICANT SIGNING (BP only) --}}
    {{-- ================================================================== --}}
    @if($isBP)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Signing
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500">Date Signed</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_date_signed ? \Carbon\Carbon::parse($application->applicant_date_signed)->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Gov't ID No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_govt_id ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">ID Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_id_date_issued ? $application->applicant_id_date_issued->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Place Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_id_place_issued ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 11. CONSENT (LOT OWNER / AUTHORIZED REP) (BP only) --}}
    {{-- ================================================================== --}}
    @if($isBP)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Consent (Lot Owner / Authorized Representative)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Name</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Address</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_address ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Date Signed</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_date_signed ? $application->owner_date_signed->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Gov't ID No.</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_govt_id ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">ID Date Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_id_date_issued ? $application->owner_id_date_issued->format('M d, Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Place Issued</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->owner_id_place_issued ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 12. ELECTRICAL DETAILS (BP only, if include_electrical) --}}
    {{-- ================================================================== --}}
    @if($isBP && $application->include_electrical)
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>
            <i class="fas fa-bolt mr-2 text-yellow-500"></i>Electrical Details
        </h3>

        {{-- Load capacities --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            <div>
                <p class="text-xs text-gray-500">Total Connected Load</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->total_connected_load ? number_format($application->total_connected_load, 2) . ' kW' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Transformer Capacity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->total_transformer_capacity ? number_format($application->total_transformer_capacity, 2) . ' kVA' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Generator Capacity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->total_generator_capacity ? number_format($application->total_generator_capacity, 2) . ' kW' : '—' }}</p>
            </div>
        </div>

        {{-- PEE Section --}}
        <div class="border-t border-gray-100 pt-4 mb-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Professional Electrical Engineer (PEE)</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Name</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PRC No.</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_prc_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PRC Validity</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_prc_validity ? \Carbon\Carbon::parse($application->pee_prc_validity)->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PTR No.</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_ptr_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PTR Date Issued</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_ptr_date_issued ? \Carbon\Carbon::parse($application->pee_ptr_date_issued)->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PTR Issued At</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_ptr_issued_at ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Address</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">TIN</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->pee_tin ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- SEW Section --}}
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Supervising Electrical Worker (SEW)</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Profession</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_profession ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Name</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PRC No.</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_prc_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PRC Validity</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_prc_validity ? \Carbon\Carbon::parse($application->sew_prc_validity)->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PTR No.</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_ptr_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PTR Date Issued</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_ptr_date_issued ? \Carbon\Carbon::parse($application->sew_ptr_date_issued)->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">PTR Issued At</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_ptr_issued_at ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Address</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">TIN</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->sew_tin ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 13. ASSESSMENT SUMMARY --}}
    {{-- ================================================================== --}}
    @if($application->assessments && $application->assessments->count())
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Assessment Summary
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
                <span class="text-lg font-bold text-blue-700">&#8369;{{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- 14. ACTIVITY LOG --}}
    {{-- ================================================================== --}}
    @php $sectionNum++ @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Activity Log
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
                <p class="text-sm text-red-700 mt-0.5">{{ $application->cancelled_at ? $application->cancelled_at->format('M d, Y h:i A') : '—' }}</p>
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
