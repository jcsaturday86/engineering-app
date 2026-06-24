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

    {{-- Application Summary --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-blue-500 mr-1"></i> Application Summary
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Application No.</dt>
                <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $application->application_number }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Applicant</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Project Title</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $application->project_title ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Building Location</dt>
                <dd class="font-medium text-gray-900 mt-0.5">
                    {{ $application->building_street ?? '' }}{{ $application->building_street && $application->buildingBarangay ? ', ' : '' }}{{ $application->buildingBarangay->name ?? '' }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Scope of Work</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $application->scope_of_work ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Total Estimated Cost</dt>
                <dd class="font-medium text-gray-900 mt-0.5">
                    @if($application->total_estimated_cost)
                        &#8369;{{ number_format($application->total_estimated_cost, 2) }}
                    @else
                        -
                    @endif
                </dd>
            </div>
        </div>
    </div>

    {{-- Zoning Assessment Form --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-6">
            <i class="fas fa-map-marked-alt text-blue-500 mr-1"></i> Zoning Assessment Form
        </h3>

        <form action="{{ route('zoning.store', $application) }}" method="POST" autocomplete="off">
            @csrf

            {{-- Section 1: Project Classification --}}
            <div class="mb-8">
                <h4 class="text-sm font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    1. Project Classification
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="project_lifespan" class="block text-sm font-medium text-gray-700 mb-1">Project Lifespan</label>
                        <select name="project_lifespan" id="project_lifespan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select...</option>
                            <option value="Short-term" {{ old('project_lifespan', $zoningAssessment->project_lifespan ?? '') === 'Short-term' ? 'selected' : '' }}>Short-term</option>
                            <option value="Medium-term" {{ old('project_lifespan', $zoningAssessment->project_lifespan ?? '') === 'Medium-term' ? 'selected' : '' }}>Medium-term</option>
                            <option value="Long-term" {{ old('project_lifespan', $zoningAssessment->project_lifespan ?? '') === 'Long-term' ? 'selected' : '' }}>Long-term</option>
                        </select>
                        @error('project_lifespan')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="project_significance" class="block text-sm font-medium text-gray-700 mb-1">Project Significance</label>
                        <select name="project_significance" id="project_significance" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select...</option>
                            <option value="Local" {{ old('project_significance', $zoningAssessment->project_significance ?? '') === 'Local' ? 'selected' : '' }}>Local</option>
                            <option value="Regional" {{ old('project_significance', $zoningAssessment->project_significance ?? '') === 'Regional' ? 'selected' : '' }}>Regional</option>
                            <option value="National" {{ old('project_significance', $zoningAssessment->project_significance ?? '') === 'National' ? 'selected' : '' }}>National</option>
                        </select>
                        @error('project_significance')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="project_classification" class="block text-sm font-medium text-gray-700 mb-1">Project Classification</label>
                        <input type="text" name="project_classification" id="project_classification" value="{{ old('project_classification', $zoningAssessment->project_classification ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('project_classification')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="project_status" class="block text-sm font-medium text-gray-700 mb-1">Project Status</label>
                        <input type="text" name="project_status" id="project_status" value="{{ old('project_status', $zoningAssessment->project_status ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('project_status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Section 2: Zoning Details --}}
            <div class="mb-8">
                <h4 class="text-sm font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    2. Zoning Details
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="site_zoning_classification" class="block text-sm font-medium text-gray-700 mb-1">Site Zoning Classification</label>
                        <input type="text" name="site_zoning_classification" id="site_zoning_classification" value="{{ old('site_zoning_classification', $zoningAssessment->site_zoning_classification ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('site_zoning_classification')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="right_over_lands" class="block text-sm font-medium text-gray-700 mb-1">Right Over Lands</label>
                        <input type="text" name="right_over_lands" id="right_over_lands" value="{{ old('right_over_lands', $zoningAssessment->right_over_lands ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('right_over_lands')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="radius_covered" class="block text-sm font-medium text-gray-700 mb-1">Radius Covered</label>
                        <input type="text" name="radius_covered" id="radius_covered" value="{{ old('radius_covered', $zoningAssessment->radius_covered ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('radius_covered')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="land_use_radius" class="block text-sm font-medium text-gray-700 mb-1">Land Use Radius</label>
                        <input type="text" name="land_use_radius" id="land_use_radius" value="{{ old('land_use_radius', $zoningAssessment->land_use_radius ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('land_use_radius')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="building_coverage" class="block text-sm font-medium text-gray-700 mb-1">Building Coverage</label>
                        <input type="text" name="building_coverage" id="building_coverage" value="{{ old('building_coverage', $zoningAssessment->building_coverage ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('building_coverage')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Section 3: Boundaries --}}
            <div class="mb-8">
                <h4 class="text-sm font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    3. Boundaries
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="boundary_north" class="block text-sm font-medium text-gray-700 mb-1">Boundary North</label>
                        <input type="text" name="boundary_north" id="boundary_north" value="{{ old('boundary_north', $zoningAssessment->boundary_north ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('boundary_north')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="boundary_south" class="block text-sm font-medium text-gray-700 mb-1">Boundary South</label>
                        <input type="text" name="boundary_south" id="boundary_south" value="{{ old('boundary_south', $zoningAssessment->boundary_south ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('boundary_south')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="boundary_east" class="block text-sm font-medium text-gray-700 mb-1">Boundary East</label>
                        <input type="text" name="boundary_east" id="boundary_east" value="{{ old('boundary_east', $zoningAssessment->boundary_east ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('boundary_east')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="boundary_west" class="block text-sm font-medium text-gray-700 mb-1">Boundary West</label>
                        <input type="text" name="boundary_west" id="boundary_west" value="{{ old('boundary_west', $zoningAssessment->boundary_west ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('boundary_west')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Section 4: Compliance --}}
            <div class="mb-8">
                <h4 class="text-sm font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    4. Compliance
                </h4>
                <div class="space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="secure_ecc" value="0">
                        <input type="checkbox" name="secure_ecc" value="1"
                            {{ old('secure_ecc', $zoningAssessment->secure_ecc ?? false) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Secure ECC Required</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="off_street_parking" value="0">
                        <input type="checkbox" name="off_street_parking" value="1"
                            {{ old('off_street_parking', $zoningAssessment->off_street_parking ?? false) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Off-street Parking Required</span>
                    </label>
                </div>
            </div>

            {{-- Section 5: Evaluation --}}
            <div class="mb-8">
                <h4 class="text-sm font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    5. Evaluation
                </h4>
                <div class="space-y-4">
                    <div>
                        <label for="findings_evaluation" class="block text-sm font-medium text-gray-700 mb-1">Findings / Evaluation</label>
                        <textarea name="findings_evaluation" id="findings_evaluation" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('findings_evaluation', $zoningAssessment->findings_evaluation ?? '') }}</textarea>
                        @error('findings_evaluation')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="decision_recommended" class="block text-sm font-medium text-gray-700 mb-1">Decision Recommended</label>
                            <select name="decision_recommended" id="decision_recommended" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select...</option>
                                <option value="Approved" {{ old('decision_recommended', $zoningAssessment->decision_recommended ?? '') === 'Approved' ? 'selected' : '' }}>Approved</option>
                                <option value="Denied" {{ old('decision_recommended', $zoningAssessment->decision_recommended ?? '') === 'Denied' ? 'selected' : '' }}>Denied</option>
                                <option value="Conditional" {{ old('decision_recommended', $zoningAssessment->decision_recommended ?? '') === 'Conditional' ? 'selected' : '' }}>Conditional</option>
                            </select>
                            @error('decision_recommended')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="date_evaluation" class="block text-sm font-medium text-gray-700 mb-1">Date of Evaluation</label>
                            <input type="date" name="date_evaluation" id="date_evaluation" value="{{ old('date_evaluation', $zoningAssessment->date_evaluation ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('date_evaluation')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="decision_no" class="block text-sm font-medium text-gray-700 mb-1">Decision No.</label>
                            <input type="number" name="decision_no" id="decision_no" value="{{ old('decision_no', $zoningAssessment->decision_no ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('decision_no')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="certificate_date" class="block text-sm font-medium text-gray-700 mb-1">Certificate Date</label>
                            <input type="date" name="certificate_date" id="certificate_date" value="{{ old('certificate_date', $zoningAssessment->certificate_date ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('certificate_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="assessed_by" class="block text-sm font-medium text-gray-700 mb-1">Assessed By</label>
                            <input type="text" name="assessed_by" id="assessed_by" value="{{ old('assessed_by', $zoningAssessment->assessed_by ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('assessed_by')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Button Row --}}
            <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-save"></i> Save Draft
                </button>
            </div>
        </form>

        {{-- Separate forms for Finalize / Skip (outside the main form to avoid nesting) --}}
        <div class="flex flex-wrap items-center gap-3 mt-3">
            @if($zoningAssessment)
                <form action="{{ route('zoning.finalize', $application) }}" method="POST" class="inline" autocomplete="off">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-check-circle"></i> Finalize
                    </button>
                </form>
            @endif

            @if(!$zoningAssessment)
                <form action="{{ route('zoning.skip', $application) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to skip zoning assessment for this application?')" autocomplete="off">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition">
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
