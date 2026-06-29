@extends('layouts.app')

@section('title', 'Zoning Assessment')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('zoning.index') }}" class="text-gray-500 hover:text-gray-700">Zoning</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Assess {{ $application->application_number }}</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Zoning Assessment</h2>
        <a href="{{ route('zoning.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    {{-- Application Details --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
        <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-200 pb-2">
            <i class="fas fa-info-circle text-blue-500 mr-1"></i> Application Details
        </h3>

        {{-- Row 1: App No, Type, Project Title, Complexity, Scope --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-500">Application No.</p>
                <p class="font-mono font-medium text-gray-900 mt-0.5">{{ $application->application_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Application Type</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicationType?->name ?? '—' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs text-gray-500">Project Title</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->project_title ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Complexity</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->complexity ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Scope of Work</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->scopeOfWork?->name ?? '—' }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs text-gray-500">Scope of Work Details</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $application->scope_of_work_details ?? '—' }}</p>
            </div>
        </div>

        {{-- Applicant Information --}}
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Applicant Information</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Full Name</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }} {{ $application->applicant_middle_name }} {{ $application->applicant_suffix }}</p>
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
                <div>
                    <p class="text-xs text-gray-500">Address</p>
                    <p class="text-sm text-gray-900 mt-0.5">
                        {{ $application->applicant_street ?? '' }}{{ $application->applicant_street ? ', ' : '' }}{{ $application->applicantBarangay?->name ?? '' }}{{ $application->applicantBarangay ? ', ' : '' }}{{ $application->applicantCity?->name ?? '' }}{{ $application->applicantCity ? ', ' : '' }}{{ $application->applicantProvince?->name ?? '' }}
                        @if($application->applicant_zip_code) {{ $application->applicant_zip_code }} @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Enterprise</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->enterprise_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Form of Ownership</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->formOfOwnership?->name ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Location of Construction --}}
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Location of Construction</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
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

        {{-- Building Specs & Character of Occupancy --}}
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Building Specifications</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500">No. of Storey/s</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->no_of_storeys ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">No. of Units</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->no_of_units ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total Floor Area (sq.m.)</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Lot Area (sq.m.)</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->lot_area ? number_format($application->lot_area, 2) : '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Character of Occupancy --}}
        @if($application->applicationOccupancyGroups && $application->applicationOccupancyGroups->count())
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Character of Occupancy</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($application->applicationOccupancyGroups as $og)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $og->occupancySubGroup?->occupancyGroup?->code }}: {{ $og->occupancySubGroup?->name }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Cost Estimates --}}
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Cost Estimates</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Building</p>
                    <p class="text-sm text-gray-900 mt-0.5">&#8369;{{ number_format($application->building_cost ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Electrical</p>
                    <p class="text-sm text-gray-900 mt-0.5">&#8369;{{ number_format($application->electrical_cost ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Mechanical</p>
                    <p class="text-sm text-gray-900 mt-0.5">&#8369;{{ number_format($application->mechanical_cost ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Electronics</p>
                    <p class="text-sm text-gray-900 mt-0.5">&#8369;{{ number_format($application->electronics_cost ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Plumbing</p>
                    <p class="text-sm text-gray-900 mt-0.5">&#8369;{{ number_format($application->plumbing_cost ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center justify-between max-w-xs">
                    <p class="text-sm font-semibold text-blue-700">Total Estimated Cost</p>
                    <p class="text-sm font-bold text-blue-700">&#8369;{{ number_format($application->total_estimated_cost ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="border-t border-gray-100 pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Proposed Date of Construction</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->proposed_construction_date ? $application->proposed_construction_date->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Expected Date of Completion</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->expected_completion_date ? $application->expected_completion_date->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Date Submitted</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->submitted_at ? $application->submitted_at->format('M d, Y g:i A') : '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Zoning Assessment Form --}}
    @php
        $savedLifespan = old('project_lifespan', $zoningAssessment->project_lifespan ?? '');
        $savedSignificance = old('project_significance', $zoningAssessment->project_significance ?? '');
        $savedRadius = old('radius_covered', $zoningAssessment->radius_covered ?? '');
        $savedStatus = old('project_status', $zoningAssessment->project_status ?? '');
        $statusIsOthers = $savedStatus && !in_array($savedStatus, ['Proposed', 'Operational', 'Completed']);
        $zSectionNum = 0;
    @endphp

    <form action="{{ route('zoning.store', $application) }}" method="POST" autocomplete="off">
        @csrf

        <div class="space-y-4">

        {{-- 1. Project Classification --}}
        @php $zSectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['project_lifespan','project_significance','project_classification','radius_covered','project_status']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3"
            x-data="{ projectStatus: '{{ $statusIsOthers ? 'Others' : $savedStatus }}', statusOther: '{{ $statusIsOthers ? str_replace('Others: ', '', $savedStatus) : '' }}' }">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $zSectionNum }}</span>Project Classification
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="project_lifespan" class="block text-xs font-medium text-gray-600 mb-1">Project Lifespan <span class="text-red-500">*</span></label>
                    <select name="project_lifespan" id="project_lifespan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        <option value="Permanent" {{ $savedLifespan === 'Permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="Temporary" {{ $savedLifespan === 'Temporary' ? 'selected' : '' }}>Temporary</option>
                    </select>
                    @error('project_lifespan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="project_significance" class="block text-xs font-medium text-gray-600 mb-1">Project Significance <span class="text-red-500">*</span></label>
                    <select name="project_significance" id="project_significance" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        <option value="Regular" {{ $savedSignificance === 'Regular' ? 'selected' : '' }}>Regular</option>
                        <option value="Special" {{ $savedSignificance === 'Special' ? 'selected' : '' }}>Special</option>
                    </select>
                    @error('project_significance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="project_classification" class="block text-xs font-medium text-gray-600 mb-1">Project Classification <span class="text-red-500">*</span></label>
                    <input type="text" name="project_classification" id="project_classification" required
                        value="{{ old('project_classification', $zoningAssessment->project_classification ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('project_classification') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="radius_covered" class="block text-xs font-medium text-gray-600 mb-1">Radius Covered from Lot Boundary <span class="text-red-500">*</span></label>
                    <select name="radius_covered" id="radius_covered" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        <option value="100m (Regular Project)" {{ $savedRadius === '100m (Regular Project)' ? 'selected' : '' }}>100m (Regular Project)</option>
                        <option value="1km (Special Project)" {{ $savedRadius === '1km (Special Project)' ? 'selected' : '' }}>1km (Special Project)</option>
                    </select>
                    @error('radius_covered') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Project Status <span class="text-red-500">*</span></label>
                    <select x-model="projectStatus" @change="if (projectStatus !== 'Others') statusOther = ''" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        <option value="Proposed">Proposed</option>
                        <option value="Operational">Operational</option>
                        <option value="Completed">Completed</option>
                        <option value="Others">Others (Specify)</option>
                    </select>
                    <input type="hidden" name="project_status" :value="projectStatus === 'Others' ? 'Others: ' + statusOther : projectStatus">
                    <div x-show="projectStatus === 'Others'" x-cloak class="mt-2">
                        <input type="text" x-model="statusOther" placeholder="Specify status..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    @error('project_status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 2. Zoning Details --}}
        @php $zSectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $zSectionNum }}</span>Zoning Details
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label for="site_zoning_classification" class="block text-xs font-medium text-gray-600 mb-1">Site Zoning Classification</label>
                    <input type="text" name="site_zoning_classification" id="site_zoning_classification"
                        value="{{ old('site_zoning_classification', $zoningAssessment->site_zoning_classification ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('site_zoning_classification') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="right_over_lands" class="block text-xs font-medium text-gray-600 mb-1">Right Over Lands</label>
                    <input type="text" name="right_over_lands" id="right_over_lands"
                        value="{{ old('right_over_lands', $zoningAssessment->right_over_lands ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('right_over_lands') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="land_use_radius" class="block text-xs font-medium text-gray-600 mb-1">Land Use Radius</label>
                    <input type="text" name="land_use_radius" id="land_use_radius"
                        value="{{ old('land_use_radius', $zoningAssessment->land_use_radius ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('land_use_radius') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="building_coverage" class="block text-xs font-medium text-gray-600 mb-1">Building Coverage</label>
                    <input type="text" name="building_coverage" id="building_coverage"
                        value="{{ old('building_coverage', $zoningAssessment->building_coverage ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('building_coverage') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 3. Boundaries --}}
        @php $zSectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $zSectionNum }}</span>Boundaries
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="boundary_north" class="block text-xs font-medium text-gray-600 mb-1">Boundary North</label>
                    <input type="text" name="boundary_north" id="boundary_north"
                        value="{{ old('boundary_north', $zoningAssessment->boundary_north ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('boundary_north') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="boundary_south" class="block text-xs font-medium text-gray-600 mb-1">Boundary South</label>
                    <input type="text" name="boundary_south" id="boundary_south"
                        value="{{ old('boundary_south', $zoningAssessment->boundary_south ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('boundary_south') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="boundary_east" class="block text-xs font-medium text-gray-600 mb-1">Boundary East</label>
                    <input type="text" name="boundary_east" id="boundary_east"
                        value="{{ old('boundary_east', $zoningAssessment->boundary_east ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('boundary_east') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="boundary_west" class="block text-xs font-medium text-gray-600 mb-1">Boundary West</label>
                    <input type="text" name="boundary_west" id="boundary_west"
                        value="{{ old('boundary_west', $zoningAssessment->boundary_west ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('boundary_west') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 4. Compliance --}}
        @php $zSectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $zSectionNum }}</span>Compliance
            </h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <input type="hidden" name="secure_ecc" value="0">
                    <input type="checkbox" name="secure_ecc" value="1"
                        {{ old('secure_ecc', $zoningAssessment->secure_ecc ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Secure ECC Required</span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition cursor-pointer">
                    <input type="hidden" name="off_street_parking" value="0">
                    <input type="checkbox" name="off_street_parking" value="1"
                        {{ old('off_street_parking', $zoningAssessment->off_street_parking ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Off-street Parking Required</span>
                </label>
            </div>
        </div>

        {{-- Save Compliance Button --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                <i class="fas fa-save"></i> Save Assessment Details
            </button>
        </div>

        </div>{{-- close space-y-4 --}}
    </form>

    <div class="space-y-4">

        {{-- ================================================================== --}}
        {{-- 5. ZONING ASSESSMENT FEES --}}
        {{-- ================================================================== --}}
        @php $zSectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-3">
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                    <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $zSectionNum }}</span>Zoning Assessment Fees
                </h3>
                <form action="{{ route('zoning.autoCompute', $application) }}" method="POST" class="inline" autocomplete="off">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-calculator"></i> Auto Compute
                    </button>
                </form>
            </div>

            {{-- Fee Items Table --}}
            <div class="overflow-x-auto" x-data="{
                selected: [],
                allIds: @js($assessmentItems->pluck('id')->values()),
                get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                toggleAll() {
                    this.selected = this.allSelected ? [] : [...this.allIds];
                }
            }">
                @if($assessmentItems->count())
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-500" x-show="selected.length > 0" x-cloak>
                        <span x-text="selected.length"></span> item(s) selected
                    </p>
                    <form x-show="selected.length > 0" x-cloak
                        action="{{ route('zoning.removeItems', $application) }}" method="POST" class="inline"
                        @submit.prevent="if(confirm('Remove ' + selected.length + ' selected item(s)?')) { $el.submit(); }" autocomplete="off">
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="item_ids[]" :value="id">
                        </template>
                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-trash-alt"></i> Remove Selected
                        </button>
                    </form>
                </div>
                @endif
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            @if($assessmentItems->count())
                            <th class="px-3 py-3 w-10">
                                <input type="checkbox" @click="toggleAll()" :checked="allSelected"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            @endif
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Fee Code</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Description</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Cost Basis</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Excess Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($assessmentItems as $item)
                        <tr class="hover:bg-gray-50" :class="selected.includes({{ $item->id }}) && 'bg-blue-50'">
                            <td class="px-3 py-3">
                                <input type="checkbox" value="{{ $item->id }}" x-model.number="selected"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-700">{{ $item->fee_code }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->excess_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('zoning.removeItem', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this item?');" autocomplete="off">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-receipt text-2xl mb-2"></i>
                                <p>No fee items yet. Use the form below to add fee items.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($assessmentItems->count())
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Total</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($assessmentItems->sum('amount'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 6. ADD FEE ITEM --}}
        {{-- ================================================================== --}}
        @php $zSectionNum++ @endphp
        @php
            $subGroupsJs = $occupancyGroups->flatMap(fn($g) => $g->subGroups->map(fn($sg) => [
                'id' => $sg->id,
                'label' => $g->code . ': ' . $sg->name,
                'group' => $g->code . ' - ' . $g->name,
            ]));
            $otherFeesJs = $otherFees->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'amount' => (float) $f->amount]);
            $certAmount = $certFee ? (float) $certFee->amount : 0;
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3" x-data="{
            feeType: '',
            subGroups: @js($subGroupsJs),
            otherFees: @js($otherFeesJs),
            certAmount: {{ $certAmount }},
            selectedSubGroup: '',
            selectedOtherFee: '',
            manualDescription: '',
            manualAmount: 0,
            get selectedOtherFeeAmount() {
                if (!this.selectedOtherFee) return 0;
                const fee = this.otherFees.find(f => f.id == this.selectedOtherFee);
                return fee ? fee.amount : 0;
            },
            formatPeso(val) {
                return parseFloat(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            resetFields() {
                this.selectedSubGroup = '';
                this.selectedOtherFee = '';
                this.manualDescription = '';
                this.manualAmount = 0;
            }
        }" x-init="$watch('feeType', () => resetFields())">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $zSectionNum }}</span>Add Fee Item
            </h3>

            <form action="{{ route('zoning.addItem', $application) }}" method="POST" autocomplete="off">
                @csrf

                {{-- Fee Type Selector --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fee Type <span class="text-red-500">*</span></label>
                    <select x-model="feeType" name="fee_type" required
                        class="w-full sm:w-80 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Fee Type --</option>
                        <option value="lc">Locational Clearance</option>
                        <option value="lc_manual">Locational Clearance (Manual Entry)</option>
                        <option value="cert">Zoning Certification</option>
                        <option value="others">Others</option>
                    </select>
                </div>

                {{-- LC: Choose Sub-group → auto-compute --}}
                <div x-show="feeType === 'lc'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Occupancy Sub-Group <span class="text-red-500">*</span></label>
                            <select name="occupancy_sub_group_id" x-model="selectedSubGroup"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select Sub-Group --</option>
                                <template x-for="sg in subGroups" :key="sg.id">
                                    <option :value="sg.id" x-text="sg.label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Total Estimated Cost</label>
                            <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-medium">
                                &#8369;{{ number_format($application->total_estimated_cost, 2) }}
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Amount will be auto-computed based on fee schedule.</p>
                        </div>
                    </div>
                </div>

                {{-- LC Manual: Enter description + amount --}}
                <div x-show="feeType === 'lc_manual'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description <span class="text-red-500">*</span></label>
                            <input type="text" name="manual_description" x-model="manualDescription"
                                placeholder="Enter fee description"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">&#8369;</span>
                                <input type="number" name="manual_amount" x-model.number="manualAmount" step="0.01" min="0.01"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Certification: Choose Sub-group → flat fee --}}
                <div x-show="feeType === 'cert'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Occupancy Sub-Group <span class="text-red-500">*</span></label>
                            <select name="cert_sub_group_id" x-model="selectedSubGroup"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select Sub-Group --</option>
                                <template x-for="sg in subGroups" :key="sg.id">
                                    <option :value="sg.id" x-text="sg.label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Certification Fee</label>
                            <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-medium">
                                &#8369;<span x-text="formatPeso(certAmount)">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Others: Variance / Non-Conforming --}}
                <div x-show="feeType === 'others'" x-cloak class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Select Fee <span class="text-red-500">*</span></label>
                            <select name="other_fee_id" x-model="selectedOtherFee"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <template x-for="fee in otherFees" :key="fee.id">
                                    <option :value="fee.id" x-text="fee.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                            <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-medium">
                                &#8369;<span x-text="formatPeso(selectedOtherFeeAmount)">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div x-show="feeType" x-cloak class="mt-4">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus"></i> Add Fee Item
                    </button>
                </div>
            </form>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-3" x-data="{ showFinalizeModal: false, finalizePassword: '', passwordError: '' }">
            @if($assessment && $assessment->status !== 'finalized' && $assessmentItems->count())
                <button @click="showFinalizeModal = true; finalizePassword = ''; passwordError = ''"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                    <i class="fas fa-check-circle"></i> Finalize Assessment
                </button>

                {{-- Password Confirmation Modal --}}
                <div x-show="showFinalizeModal" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    @keydown.escape.window="showFinalizeModal = false">
                    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.outside="showFinalizeModal = false">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="inline-flex items-center justify-center w-10 h-10 bg-yellow-100 rounded-full">
                                <i class="fas fa-lock text-yellow-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Confirm Finalization</h3>
                                <p class="text-sm text-gray-500">This action cannot be undone.</p>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mb-4">
                            Enter your password to confirm that the zoning assessment for
                            <strong>{{ $application->application_number }}</strong> is correct and ready to finalize.
                        </p>

                        @if($errors->has('password'))
                            <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                                {{ $errors->first('password') }}
                            </div>
                        @endif

                        <form action="{{ route('zoning.finalize', $application) }}" method="POST" autocomplete="off">
                            @csrf
                            <div class="mb-4">
                                <label for="finalize_password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password" id="finalize_password" x-model="finalizePassword" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Enter your account password">
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="showFinalizeModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!finalizePassword"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-check-circle"></i> Confirm & Finalize
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if(!$zoningAssessment->exists && !$assessment)
                <form action="{{ route('zoning.skip', $application) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to skip zoning assessment for this application?')" autocomplete="off">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition shadow-sm">
                        <i class="fas fa-forward"></i> Skip Zoning
                    </button>
                </form>
            @endif

            <a href="{{ route('zoning.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        </div>
</div>

@endsection

@if($errors->has('password'))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            const el = document.querySelector('[x-data*="showFinalizeModal"]');
            if (el && el._x_dataStack) {
                el._x_dataStack[0].showFinalizeModal = true;
            }
        }, 100);
    });
</script>
@endpush
@endif
