@extends('layouts.app')

@section('title', $application ? 'Edit Application' : 'New Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('applications.index') }}" class="text-gray-500 hover:text-gray-700">Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">
        {{ $application ? 'Edit ' . $application->application_number : 'New ' . $permitType->name . ' Application' }}
    </span>
@endsection

@section('content')
@php
    $isBP = $permitType->code === 'BP';
    $isOP = $permitType->code === 'OP';

    // Pre-build occupancy selections for edit mode
    $selectedSubGroups = [];
    $selectedOthersText = [];
    if ($application && $application->applicationOccupancyGroups) {
        foreach ($application->applicationOccupancyGroups as $aog) {
            $selectedSubGroups[] = $aog->occupancy_sub_group_id;
            if ($aog->others_text) {
                $selectedOthersText[$aog->occupancy_sub_group_id] = $aog->others_text;
            }
        }
    }
    if (old('occupancy_sub_groups')) {
        $selectedSubGroups = old('occupancy_sub_groups');
    }

    // Pre-parse applies_to for checkboxes
    $appliesToValues = [];
    $oldAppliesTo = old('applies_to', $application->applies_to ?? '');
    if ($oldAppliesTo) {
        $appliesToValues = array_map('trim', explode(',', $oldAppliesTo));
    }

    // Section counter for BP vs OP
    $sectionNum = 0;
@endphp

<form
    method="POST"
    action="{{ $application ? route('applications.update', $application) : route('applications.store') }}"
    x-data="applicationForm()"
    onsubmit="return validateOccupancy();"
    autocomplete="off"
>
    @csrf
    @if($application)
        @method('PUT')
    @endif
    <input type="hidden" name="permit_type_id" value="{{ $permitType->id }}">

    <div class="space-y-4">
        {{-- Compact Form Header --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-md {{ $isBP ? 'bg-blue-600 text-white' : 'bg-indigo-600 text-white' }}">{{ $permitType->code }}</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $application ? 'Edit Application' : 'New ' . $permitType->name . ' Application' }}
                    </h2>
                    @if($application)
                        <p class="text-xs text-gray-500">{{ $application->application_number }}</p>
                    @endif
                </div>
            </div>
            <p class="text-xs text-gray-400">Fields marked with <span class="text-red-500">*</span> are required</p>
        </div>

        {{-- ================================================================== --}}
        {{-- 1. APPLICATION HEADER --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 {{ $isBP ? 'bg-blue-600' : 'bg-indigo-600' }} text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>{{ $isOP ? 'Occupancy Permit Application' : 'Application Details' }}
            </h3>

            {{-- Application Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Application Type <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    @foreach($applicationTypes as $type)
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="application_type_id" value="{{ $type->id }}"
                                class="w-4 h-4 {{ $isOP ? 'text-indigo-600 focus:ring-indigo-500' : 'text-blue-600 focus:ring-blue-500' }} border-gray-300"
                                {{ old('application_type_id', $application->application_type_id ?? '') == $type->id ? 'checked' : '' }} required>
                            <span class="text-sm text-gray-700">{{ $type->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('application_type_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- BP: Project Title --}}
            @if($isBP)
            <div>
                <label for="project_title" class="block text-xs font-medium text-gray-600 mb-1">Project Title <span class="text-red-500">*</span></label>
                <input type="text" name="project_title" id="project_title"
                    value="{{ old('project_title', $application->project_title ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required>
                @error('project_title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            {{-- BP: Complexity --}}
            @if($isBP)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Complexity <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="complexity" value="Simple" required
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            {{ old('complexity', $application->complexity ?? '') === 'Simple' ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Simple</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="complexity" value="Complex" required
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            {{ old('complexity', $application->complexity ?? '') === 'Complex' ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Complex</span>
                    </label>
                </div>
                @error('complexity')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            {{-- BP: Skip Locational Clearance --}}
            @if($isBP)
            <div>
                <label class="inline-flex items-start gap-2 cursor-pointer p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <input type="checkbox" name="skip_locational" value="1"
                        class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 mt-0.5"
                        {{ old('skip_locational', $application->applies_to ?? '') === 'SKIP_LC' ? 'checked' : '' }}>
                    <div>
                        <span class="text-sm font-medium text-amber-800">Skip Locational Clearance</span>
                        <p class="text-xs text-amber-600 mt-0.5">Goes directly to Engineering Assessment instead of Planning Office.</p>
                    </div>
                </label>
            </div>
            @endif
        </div>

        {{-- ================================================================== --}}
        {{-- 2. APPLICANT INFORMATION --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
            </h3>

            {{-- Name row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_first_name" class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_first_name" id="applicant_first_name"
                        value="{{ old('applicant_first_name', $application->applicant_first_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_first_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_middle_name" class="block text-xs font-medium text-gray-600 mb-1">Middle Name</label>
                    <input type="text" name="applicant_middle_name" id="applicant_middle_name"
                        value="{{ old('applicant_middle_name', $application->applicant_middle_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_middle_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_last_name" class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_last_name" id="applicant_last_name"
                        value="{{ old('applicant_last_name', $application->applicant_last_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_last_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_suffix" class="block text-xs font-medium text-gray-600 mb-1">Suffix</label>
                    <input type="text" name="applicant_suffix" id="applicant_suffix"
                        value="{{ old('applicant_suffix', $application->applicant_suffix ?? '') }}"
                        placeholder="Jr., Sr., III"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_suffix')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- TIN (BP only), Contact, Email --}}
            <div class="grid grid-cols-1 sm:grid-cols-{{ $isBP ? '3' : '2' }} gap-3">
                @if($isBP)
                <div>
                    <label for="applicant_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_tin" required id="applicant_tin"
                        value="{{ old('applicant_tin', $application->applicant_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_tin')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endif
                <div>
                    <label for="applicant_contact_no" class="block text-xs font-medium text-gray-600 mb-1">Contact No. <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_contact_no" required id="applicant_contact_no"
                        value="{{ old('applicant_contact_no', $application->applicant_contact_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_contact_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_email" class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="applicant_email" id="applicant_email"
                        value="{{ old('applicant_email', $application->applicant_email ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Enterprise & Ownership (BP only) --}}
            @if($isBP)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="enterprise_name" class="block text-xs font-medium text-gray-600 mb-1">For Construction Owned By an Enterprise</label>
                    <input type="text" name="enterprise_name" id="enterprise_name"
                        value="{{ old('enterprise_name', $application->enterprise_name ?? '') }}"
                        placeholder="Enterprise name (if applicable)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('enterprise_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="form_of_ownership_id" class="block text-xs font-medium text-gray-600 mb-1">Form of Ownership</label>
                    <select name="form_of_ownership_id" id="form_of_ownership_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($formOfOwnerships as $ownership)
                            <option value="{{ $ownership->id }}"
                                {{ old('form_of_ownership_id', $application->form_of_ownership_id ?? '') == $ownership->id ? 'selected' : '' }}>
                                {{ $ownership->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('form_of_ownership_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endif
        </div>

        {{-- ================================================================== --}}
        {{-- 3. APPLICANT ADDRESS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
            </h3>

            {{-- Province / City / Barangay cascading --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="applicant_province_id" class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-500">*</span></label>
                    <select name="applicant_province_id" required id="applicant_province_id" x-model="selectedProvince"
                        @change="selectedCity = ''; selectedBarangay = '';" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Province --</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}"
                                {{ old('applicant_province_id', $application->applicant_province_id ?? '') == $province->id ? 'selected' : '' }}>
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('applicant_province_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_city_id" class="block text-xs font-medium text-gray-600 mb-1">City/Municipality <span class="text-red-500">*</span></label>
                    <select name="applicant_city_id" required id="applicant_city_id" x-model="selectedCity"
                        @change="selectedBarangay = '';" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select City --</option>
                        <template x-for="city in filteredCities" :key="city.id">
                            <option :value="city.id" x-text="city.name" :selected="city.id == selectedCity"></option>
                        </template>
                    </select>
                    @error('applicant_city_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_barangay_id" class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="applicant_barangay_id" id="applicant_barangay_id" x-model="selectedBarangay" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Barangay --</option>
                        <template x-for="brgy in filteredBarangays" :key="brgy.id">
                            <option :value="brgy.id" x-text="brgy.name" :selected="brgy.id == selectedBarangay"></option>
                        </template>
                    </select>
                    @error('applicant_barangay_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Street & Zip --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="applicant_street" class="block text-xs font-medium text-gray-600 mb-1">No./Street/Bldg</label>
                    <input type="text" name="applicant_street" id="applicant_street"
                        value="{{ old('applicant_street', $application->applicant_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_street')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_zip_code" class="block text-xs font-medium text-gray-600 mb-1">Zip Code</label>
                    <input type="text" name="applicant_zip_code" id="applicant_zip_code"
                        value="{{ old('applicant_zip_code', $application->applicant_zip_code ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_zip_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 4. LOCATION OF CONSTRUCTION (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Location of Construction
            </h3>

            {{-- Lot, Block, TCT, Tax Dec --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="lot_no" class="block text-xs font-medium text-gray-600 mb-1">Lot No. <span class="text-red-500">*</span></label>
                    <input type="text" name="lot_no" required id="lot_no"
                        value="{{ old('lot_no', $application->lot_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('lot_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="block_no" class="block text-xs font-medium text-gray-600 mb-1">Blk No. <span class="text-red-500">*</span></label>
                    <input type="text" name="block_no" required id="block_no"
                        value="{{ old('block_no', $application->block_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('block_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tct_no" class="block text-xs font-medium text-gray-600 mb-1">TCT No. <span class="text-red-500">*</span></label>
                    <input type="text" name="tct_no" required id="tct_no"
                        value="{{ old('tct_no', $application->tct_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('tct_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tax_dec_no" class="block text-xs font-medium text-gray-600 mb-1">Tax Dec. No. <span class="text-red-500">*</span></label>
                    <input type="text" name="tax_dec_no" required id="tax_dec_no"
                        value="{{ old('tax_dec_no', $application->tax_dec_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('tax_dec_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Land Classification, Street, Barangay --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="land_classification_id" class="block text-xs font-medium text-gray-600 mb-1">Land Classification <span class="text-red-500">*</span></label>
                    <select name="land_classification_id" id="land_classification_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($landClassifications as $lc)
                            <option value="{{ $lc->id }}"
                                {{ old('land_classification_id', $application->land_classification_id ?? '') == $lc->id ? 'selected' : '' }}>
                                {{ $lc->name }} ({{ $lc->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('land_classification_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="building_street" class="block text-xs font-medium text-gray-600 mb-1">Street <span class="text-red-500">*</span></label>
                    <input type="text" name="building_street" required id="building_street"
                        value="{{ old('building_street', $application->building_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('building_street')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="building_barangay_id" class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="building_barangay_id" id="building_barangay_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($sfcBarangays as $brgy)
                            <option value="{{ $brgy->id }}"
                                {{ old('building_barangay_id', $application->building_barangay_id ?? '') == $brgy->id ? 'selected' : '' }}>
                                {{ $brgy->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('building_barangay_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">City / Municipality</label>
                    <input type="text" value="City of San Fernando, La Union" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600">
                </div>
            </div>
        </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 5. SCOPE OF WORK (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3" x-data="{ selectedScope: '{{ old('scope_of_work_id', $application->scope_of_work_id ?? '') }}' }">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work <span class="text-red-500">*</span>
            </h3>

            <div class="space-y-1">
                @foreach($scopeOfWorks as $scope)
                <div class="px-3 py-2 rounded-lg transition-colors"
                    :class="selectedScope === '{{ $scope->id }}' ? 'bg-blue-50 ring-1 ring-blue-200' : 'hover:bg-gray-50'">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-52">
                            <input type="radio" name="scope_of_work_id" value="{{ $scope->id }}"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                x-model="selectedScope"
                                @click="selectedScope = '{{ $scope->id }}'"
                                {{ old('scope_of_work_id', $application->scope_of_work_id ?? '') == $scope->id ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ $scope->name }}</span>
                        </label>
                        @if($scope->name !== 'New Construction')
                        <input type="text" name="scope_detail_{{ $scope->id }}"
                            value="{{ old('scope_detail_' . $scope->id, ($application->scope_of_work_id ?? '') == $scope->id ? ($application->scope_of_work_details ?? '') : '') }}"
                            :disabled="selectedScope !== '{{ $scope->id }}'"
                            class="w-full sm:flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400"
                            placeholder="Specify details...">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @error('scope_of_work_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 6. CHARACTER OF OCCUPANCY --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 {{ $isBP ? 'bg-blue-600' : 'bg-indigo-600' }} text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>{{ $isOP ? 'Use or Character of Occupancy' : 'Character of Occupancy' }} <span class="text-red-500">*</span>
            </h3>
            <p id="occupancy-error" class="text-red-500 text-sm font-bold hidden">Please select at least one character of occupancy.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($occupancyGroups as $group)
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        {{-- Group Header --}}
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-sm font-bold text-gray-800 underline decoration-gray-400 underline-offset-2">
                                {{ $group->code }}: {{ $group->name }}
                            </span>
                        </div>
                        {{-- Sub-groups --}}
                        <div class="px-4 py-2.5 space-y-1.5">
                            @foreach($group->subGroups as $subGroup)
                                @php
                                    $isChecked = in_array($subGroup->id, (array) $selectedSubGroups);
                                    $othersVal = old("sub_group_{$subGroup->id}_others", $selectedOthersText[$subGroup->id] ?? '');
                                    $isOthers = str_contains(strtolower($subGroup->name), 'others') || str_contains(strtolower($subGroup->name), 'other');
                                @endphp
                                <div x-data="{ checked{{ $subGroup->id }}: {{ $isChecked ? 'true' : 'false' }} }">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="occupancy_sub_groups[]" value="{{ $subGroup->id }}"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                            x-model="checked{{ $subGroup->id }}"
                                            {{ $isChecked ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">{{ $subGroup->name }}</span>
                                    </label>
                                    @if($isOthers)
                                        <div x-show="checked{{ $subGroup->id }}" x-cloak
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 -translate-y-1"
                                            class="mt-1 ml-6">
                                            <input type="text" name="sub_group_{{ $subGroup->id }}_others"
                                                value="{{ $othersVal }}"
                                                placeholder="Please specify..."
                                                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('occupancy_sub_groups')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- ================================================================== --}}
        {{-- 7. BUILDING DETAILS & COST (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Building Details &amp; Cost
            </h3>

            {{-- Occupancy Classified, Units, Storeys, Floor Area, Lot Area --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label for="occupancy_classified" class="block text-xs font-medium text-gray-600 mb-1">Occupancy Classified</label>
                    <input type="text" name="occupancy_classified" id="occupancy_classified"
                        value="{{ old('occupancy_classified', $application->occupancy_classified ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('occupancy_classified')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="no_of_units" class="block text-xs font-medium text-gray-600 mb-1">No. of Units <span class="text-red-500">*</span></label>
                    <input type="number" name="no_of_units" required id="no_of_units" min="0"
                        value="{{ old('no_of_units', $application->no_of_units ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('no_of_units')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="no_of_storeys" class="block text-xs font-medium text-gray-600 mb-1">No. of Storeys <span class="text-red-500">*</span></label>
                    <input type="number" name="no_of_storeys" required id="no_of_storeys" min="0"
                        value="{{ old('no_of_storeys', $application->no_of_storeys ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('no_of_storeys')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="total_floor_area" class="block text-xs font-medium text-gray-600 mb-1">Total Floor Area SQ.M. <span class="text-red-500">*</span></label>
                    <input type="number" name="total_floor_area" required id="total_floor_area" min="0" step="any"
                        value="{{ old('total_floor_area', $application->total_floor_area ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('total_floor_area')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="lot_area" class="block text-xs font-medium text-gray-600 mb-1">Lot Area SQ.M. <span class="text-red-500">*</span></label>
                    <input type="number" name="lot_area" required id="lot_area" min="0" step="any"
                        value="{{ old('lot_area', $application->lot_area ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('lot_area')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Cost fields as table-like grid --}}
            <div class="bg-gray-50 rounded-lg p-4 space-y-2.5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Estimated Costs</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label for="building_cost" class="block text-xs font-medium text-gray-600 mb-1">Building Cost <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="building_cost" required id="building_cost" min="0" step="0.01"
                                x-model.number="costs.building_cost" required
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('building_cost')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="electrical_cost" class="block text-xs font-medium text-gray-600 mb-1">Electrical Cost <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="electrical_cost" required id="electrical_cost" min="0" step="0.01"
                                x-model.number="costs.electrical_cost" required
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('electrical_cost')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="mechanical_cost" class="block text-xs font-medium text-gray-600 mb-1">Mechanical Cost <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="mechanical_cost" required id="mechanical_cost" min="0" step="0.01"
                                x-model.number="costs.mechanical_cost" required
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('mechanical_cost')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="electronics_cost" class="block text-xs font-medium text-gray-600 mb-1">Electronics Cost <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="electronics_cost" required id="electronics_cost" min="0" step="0.01"
                                x-model.number="costs.electronics_cost" required
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('electronics_cost')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="plumbing_cost" class="block text-xs font-medium text-gray-600 mb-1">Plumbing Cost <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="plumbing_cost" required id="plumbing_cost" min="0" step="0.01"
                                x-model.number="costs.plumbing_cost" required
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('plumbing_cost')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="other_equipment_cost" class="block text-xs font-medium text-gray-600 mb-1">Equipment/Labor Cost 1</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="other_equipment_cost" id="other_equipment_cost" min="0" step="0.01"
                                x-model.number="costs.other_equipment_cost"
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('other_equipment_cost')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="equipment_cost_1" class="block text-xs font-medium text-gray-600 mb-1">Equipment/Labor Cost 2</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="equipment_cost_1" id="equipment_cost_1" min="0" step="0.01"
                                x-model.number="costs.equipment_cost_1"
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('equipment_cost_1')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="equipment_cost_2" class="block text-xs font-medium text-gray-600 mb-1">Equipment/Labor Cost 3</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="equipment_cost_2" id="equipment_cost_2" min="0" step="0.01"
                                x-model.number="costs.equipment_cost_2"
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('equipment_cost_2')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="equipment_cost_3" class="block text-xs font-medium text-gray-600 mb-1">Equipment/Labor Cost 4</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">P</span>
                            <input type="number" name="equipment_cost_3" id="equipment_cost_3" min="0" step="0.01"
                                x-model.number="costs.equipment_cost_3"
                                class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('equipment_cost_3')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Total Estimated Cost (readonly) - Prominent --}}
            <div class="bg-blue-600 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <label for="total_estimated_cost" class="block text-xs font-medium text-blue-200 mb-0.5">Total Estimated Cost</label>
                    <p class="text-blue-100 text-xs">Auto-calculated from costs above</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-bold text-white" x-text="'P ' + Number(totalEstimatedCost).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    <input type="hidden" name="total_estimated_cost" id="total_estimated_cost" step="0.01"
                        :value="totalEstimatedCost">
                </div>
            </div>
            @error('total_estimated_cost')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror

            {{-- Dates & Remarks --}}
            @if($isBP)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="proposed_construction_date" class="block text-xs font-medium text-gray-600 mb-1">Proposed Date of Construction <span class="text-red-500">*</span></label>
                    <input type="date" name="proposed_construction_date" required id="proposed_construction_date"
                        value="{{ old('proposed_construction_date', optional($application->proposed_construction_date ?? null)->format('Y-m-d') ?? ($application->proposed_construction_date ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('proposed_construction_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expected_completion_date" class="block text-xs font-medium text-gray-600 mb-1">Expected Date of Completion <span class="text-red-500">*</span></label>
                    <input type="date" name="expected_completion_date" required id="expected_completion_date"
                        value="{{ old('expected_completion_date', optional($application->expected_completion_date ?? null)->format('Y-m-d') ?? ($application->expected_completion_date ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('expected_completion_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endif

            <div>
                <label for="remarks" class="block text-xs font-medium text-gray-600 mb-1">Remarks</label>
                <input type="text" name="remarks" id="remarks"
                    value="{{ old('remarks', $application->remarks ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('remarks')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif {{-- end BP-only Building Details & Cost --}}

        {{-- ================================================================== --}}
        {{-- 9. ENGINEER / ARCHITECT (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Full-time Inspector &amp; Supervisor of Construction Works
            </h3>
            <p class="text-xs text-gray-500 -mt-1 mb-2">Representing the Owner</p>

            {{-- Name & Date Signed --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="engineer_name" class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="engineer_name" required id="engineer_name"
                        value="{{ old('engineer_name', $application->engineer_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_date_signed" class="block text-xs font-medium text-gray-600 mb-1">Date Signed <span class="text-red-500">*</span></label>
                    <input type="date" name="engineer_date_signed" required id="engineer_date_signed"
                        value="{{ old('engineer_date_signed', optional($application->engineer_date_signed ?? null)->format('Y-m-d') ?? ($application->engineer_date_signed ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_date_signed')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Address --}}
            <div>
                <label for="engineer_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                <input type="text" name="engineer_address" required id="engineer_address"
                    value="{{ old('engineer_address', $application->engineer_address ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('engineer_address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- PRC --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="engineer_prc_no" class="block text-xs font-medium text-gray-600 mb-1">PRC No. <span class="text-red-500">*</span></label>
                    <input type="text" name="engineer_prc_no" required id="engineer_prc_no"
                        value="{{ old('engineer_prc_no', $application->engineer_prc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_prc_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_prc_validity" class="block text-xs font-medium text-gray-600 mb-1">PRC Validity <span class="text-red-500">*</span></label>
                    <input type="date" name="engineer_prc_validity" required id="engineer_prc_validity"
                        value="{{ old('engineer_prc_validity', optional($application->engineer_prc_validity ?? null)->format('Y-m-d') ?? ($application->engineer_prc_validity ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_prc_validity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- PTR --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="engineer_ptr_no" class="block text-xs font-medium text-gray-600 mb-1">PTR No. <span class="text-red-500">*</span></label>
                    <input type="text" name="engineer_ptr_no" required id="engineer_ptr_no"
                        value="{{ old('engineer_ptr_no', $application->engineer_ptr_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_ptr_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_ptr_date_issued" class="block text-xs font-medium text-gray-600 mb-1">PTR Date Issued <span class="text-red-500">*</span></label>
                    <input type="date" name="engineer_ptr_date_issued" required id="engineer_ptr_date_issued"
                        value="{{ old('engineer_ptr_date_issued', optional($application->engineer_ptr_date_issued ?? null)->format('Y-m-d') ?? ($application->engineer_ptr_date_issued ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_ptr_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_ptr_issued_at" class="block text-xs font-medium text-gray-600 mb-1">PTR Issued At <span class="text-red-500">*</span></label>
                    <input type="text" name="engineer_ptr_issued_at" required id="engineer_ptr_issued_at"
                        value="{{ old('engineer_ptr_issued_at', $application->engineer_ptr_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_ptr_issued_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- TIN --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="engineer_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                    <input type="text" name="engineer_tin" required id="engineer_tin"
                        value="{{ old('engineer_tin', $application->engineer_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_tin')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 10. APPLICANT SIGNING (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Signing
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_date_signed" class="block text-xs font-medium text-gray-600 mb-1">Date Signed</label>
                    <input type="date" name="applicant_date_signed" id="applicant_date_signed"
                        value="{{ old('applicant_date_signed', $application->applicant_date_signed ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_date_signed')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_govt_id" class="block text-xs font-medium text-gray-600 mb-1">Gov't Issued ID No.</label>
                    <input type="text" name="applicant_govt_id" id="applicant_govt_id"
                        value="{{ old('applicant_govt_id', $application->applicant_govt_id ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_govt_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_id_date_issued" class="block text-xs font-medium text-gray-600 mb-1">ID Date Issued</label>
                    <input type="date" name="applicant_id_date_issued" id="applicant_id_date_issued"
                        value="{{ old('applicant_id_date_issued', optional($application->applicant_id_date_issued ?? null)->format('Y-m-d') ?? ($application->applicant_id_date_issued ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_id_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_id_place_issued" class="block text-xs font-medium text-gray-600 mb-1">Place Issued</label>
                    <input type="text" name="applicant_id_place_issued" id="applicant_id_place_issued"
                        value="{{ old('applicant_id_place_issued', $application->applicant_id_place_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_id_place_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 11. OWNER / CONSENT (LOT OWNER) (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Consent (Lot Owner / Authorized Representative)
            </h3>

            {{-- Name & Address --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="owner_name" class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                    <input type="text" name="owner_name" id="owner_name"
                        value="{{ old('owner_name', $application->owner_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_address" class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                    <input type="text" name="owner_address" id="owner_address"
                        value="{{ old('owner_address', $application->owner_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Date Signed, ID, ID Date, Place --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="owner_date_signed" class="block text-xs font-medium text-gray-600 mb-1">Date Signed</label>
                    <input type="date" name="owner_date_signed" id="owner_date_signed"
                        value="{{ old('owner_date_signed', optional($application->owner_date_signed ?? null)->format('Y-m-d') ?? ($application->owner_date_signed ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_date_signed')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_govt_id" class="block text-xs font-medium text-gray-600 mb-1">Gov't Issued ID No.</label>
                    <input type="text" name="owner_govt_id" id="owner_govt_id"
                        value="{{ old('owner_govt_id', $application->owner_govt_id ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_govt_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_id_date_issued" class="block text-xs font-medium text-gray-600 mb-1">ID Date Issued</label>
                    <input type="date" name="owner_id_date_issued" id="owner_id_date_issued"
                        value="{{ old('owner_id_date_issued', optional($application->owner_id_date_issued ?? null)->format('Y-m-d') ?? ($application->owner_id_date_issued ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_id_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_id_place_issued" class="block text-xs font-medium text-gray-600 mb-1">Place Issued</label>
                    <input type="text" name="owner_id_place_issued" id="owner_id_place_issued"
                        value="{{ old('owner_id_place_issued', $application->owner_id_place_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_id_place_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 12. ELECTRICAL PERMIT DATA (BP only) --}}
        {{-- ================================================================== --}}
        @if($isBP)
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Electrical Details
            </h3>

            {{-- Toggle Switch for Include Electrical --}}
            <div class="flex items-center gap-3">
                <input type="hidden" name="include_electrical" value="0">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="include_electrical" value="1" x-model="includeElectrical"
                        class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
                <span class="text-sm font-medium text-gray-700">Include Electrical Permit Details</span>
            </div>

            <div x-show="includeElectrical" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="space-y-4 pt-2">

                {{-- Electrical Loads --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Electrical Loads</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="total_connected_load" class="block text-xs font-medium text-gray-600 mb-1">Total Connected Load kVA <span class="text-red-500">*</span></label>
                            <input type="number" name="total_connected_load" required id="total_connected_load" min="0" step="0.01"
                                value="{{ old('total_connected_load', $application->total_connected_load ?? '') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('total_connected_load')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="total_transformer_capacity" class="block text-xs font-medium text-gray-600 mb-1">Total Transformer Capacity kVA <span class="text-red-500">*</span></label>
                            <input type="number" name="total_transformer_capacity" required id="total_transformer_capacity" min="0" step="0.01"
                                value="{{ old('total_transformer_capacity', $application->total_transformer_capacity ?? '') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('total_transformer_capacity')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="total_generator_capacity" class="block text-xs font-medium text-gray-600 mb-1">Total Generator/UPS Capacity kVA <span class="text-red-500">*</span></label>
                            <input type="number" name="total_generator_capacity" required id="total_generator_capacity" min="0" step="0.01"
                                value="{{ old('total_generator_capacity', $application->total_generator_capacity ?? '') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('total_generator_capacity')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Professional Electrical Engineer (PEE) --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Professional Electrical Engineer (PEE)</p>
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="pee_name" class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="pee_name" required id="pee_name"
                                    value="{{ old('pee_name', $application->pee_name ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="pee_date_signed" class="block text-xs font-medium text-gray-600 mb-1">Date Signed <span class="text-red-500">*</span></label>
                                <input type="date" name="pee_date_signed" required id="pee_date_signed"
                                    value="{{ old('pee_date_signed', $application->pee_date_signed ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_date_signed')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="pee_prc_no" class="block text-xs font-medium text-gray-600 mb-1">PRC No. <span class="text-red-500">*</span></label>
                                <input type="text" name="pee_prc_no" required id="pee_prc_no"
                                    value="{{ old('pee_prc_no', $application->pee_prc_no ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_prc_no')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="pee_prc_validity" class="block text-xs font-medium text-gray-600 mb-1">PRC Validity <span class="text-red-500">*</span></label>
                                <input type="date" name="pee_prc_validity" required id="pee_prc_validity"
                                    value="{{ old('pee_prc_validity', $application->pee_prc_validity ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_prc_validity')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="pee_ptr_no" class="block text-xs font-medium text-gray-600 mb-1">PTR No. <span class="text-red-500">*</span></label>
                                <input type="text" name="pee_ptr_no" required id="pee_ptr_no"
                                    value="{{ old('pee_ptr_no', $application->pee_ptr_no ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_ptr_no')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="pee_ptr_date_issued" class="block text-xs font-medium text-gray-600 mb-1">PTR Date Issued <span class="text-red-500">*</span></label>
                                <input type="date" name="pee_ptr_date_issued" required id="pee_ptr_date_issued"
                                    value="{{ old('pee_ptr_date_issued', $application->pee_ptr_date_issued ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_ptr_date_issued')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="pee_ptr_issued_at" class="block text-xs font-medium text-gray-600 mb-1">PTR Issued At <span class="text-red-500">*</span></label>
                                <input type="text" name="pee_ptr_issued_at" required id="pee_ptr_issued_at"
                                    value="{{ old('pee_ptr_issued_at', $application->pee_ptr_issued_at ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_ptr_issued_at')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="pee_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                                <input type="text" name="pee_address" required id="pee_address"
                                    value="{{ old('pee_address', $application->pee_address ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_address')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="pee_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                                <input type="text" name="pee_tin" required id="pee_tin"
                                    value="{{ old('pee_tin', $application->pee_tin ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('pee_tin')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Supervisor / In-Charge of Electrical Works --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Supervisor / In-Charge of Electrical Works</p>
                        <label class="relative inline-flex items-center cursor-pointer" x-data="{ sewCopied: false }">
                            <input type="checkbox" id="sew_same_as_pee"
                                @click="copyPeeToSew($event.target.checked); sewCopied = $event.target.checked"
                                class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2 text-xs font-medium text-gray-600">Same as PEE</span>
                            <span x-show="sewCopied" x-cloak
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-1"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                class="ml-2 text-xs text-green-600 font-medium">Details copied from PEE</span>
                        </label>
                    </div>
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="sew_profession" class="block text-xs font-medium text-gray-600 mb-1">Profession</label>
                                <select name="sew_profession" id="sew_profession"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select --</option>
                                    <option value="RME" {{ old('sew_profession', $application->sew_profession ?? '') === 'RME' ? 'selected' : '' }}>RME</option>
                                    <option value="REE" {{ old('sew_profession', $application->sew_profession ?? '') === 'REE' ? 'selected' : '' }}>REE</option>
                                    <option value="Master Electrician" {{ old('sew_profession', $application->sew_profession ?? '') === 'Master Electrician' ? 'selected' : '' }}>Master Electrician</option>
                                </select>
                                @error('sew_profession')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="sew_name" class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="sew_name" required id="sew_name"
                                    value="{{ old('sew_name', $application->sew_name ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="sew_date_signed" class="block text-xs font-medium text-gray-600 mb-1">Date Signed <span class="text-red-500">*</span></label>
                                <input type="date" name="sew_date_signed" required id="sew_date_signed"
                                    value="{{ old('sew_date_signed', $application->sew_date_signed ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_date_signed')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="sew_prc_no" class="block text-xs font-medium text-gray-600 mb-1">PRC No. <span class="text-red-500">*</span></label>
                                <input type="text" name="sew_prc_no" required id="sew_prc_no"
                                    value="{{ old('sew_prc_no', $application->sew_prc_no ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_prc_no')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="sew_prc_validity" class="block text-xs font-medium text-gray-600 mb-1">PRC Validity <span class="text-red-500">*</span></label>
                                <input type="date" name="sew_prc_validity" required id="sew_prc_validity"
                                    value="{{ old('sew_prc_validity', $application->sew_prc_validity ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_prc_validity')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="sew_ptr_no" class="block text-xs font-medium text-gray-600 mb-1">PTR No. <span class="text-red-500">*</span></label>
                                <input type="text" name="sew_ptr_no" required id="sew_ptr_no"
                                    value="{{ old('sew_ptr_no', $application->sew_ptr_no ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_ptr_no')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="sew_ptr_date_issued" class="block text-xs font-medium text-gray-600 mb-1">PTR Date Issued <span class="text-red-500">*</span></label>
                                <input type="date" name="sew_ptr_date_issued" required id="sew_ptr_date_issued"
                                    value="{{ old('sew_ptr_date_issued', $application->sew_ptr_date_issued ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_ptr_date_issued')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="sew_ptr_issued_at" class="block text-xs font-medium text-gray-600 mb-1">PTR Issued At <span class="text-red-500">*</span></label>
                                <input type="text" name="sew_ptr_issued_at" required id="sew_ptr_issued_at"
                                    value="{{ old('sew_ptr_issued_at', $application->sew_ptr_issued_at ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_ptr_issued_at')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="sew_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                                <input type="text" name="sew_address" required id="sew_address"
                                    value="{{ old('sew_address', $application->sew_address ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_address')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="sew_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                                <input type="text" name="sew_tin" required id="sew_tin"
                                    value="{{ old('sew_tin', $application->sew_tin ?? '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('sew_tin')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @endif
    </div>

    {{-- ================================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
        <a href="{{ route('applications.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-times text-xs"></i> Cancel
        </a>
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
            <i class="fas fa-save text-xs"></i> {{ $application ? 'Update Application' : 'Create Application' }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function applicationForm() {
        return {
            // Address cascading
            selectedProvince: '{{ old('applicant_province_id', $application->applicant_province_id ?? '') }}',
            selectedCity: '{{ old('applicant_city_id', $application->applicant_city_id ?? '') }}',
            selectedBarangay: '{{ old('applicant_barangay_id', $application->applicant_barangay_id ?? '') }}',
            cities: @json($cities),
            barangays: @json($barangays),

            get filteredCities() {
                if (!this.selectedProvince) return [];
                return this.cities.filter(c => String(c.province_id) === String(this.selectedProvince));
            },
            get filteredBarangays() {
                if (!this.selectedCity) return [];
                return this.barangays.filter(b => String(b.city_id) === String(this.selectedCity));
            },

            // Applies To checkboxes (BP only)
            appliesToChecks: @json($appliesToValues),

            syncAppliesTo() {
                if (this.$refs.appliesToInput) {
                    this.$refs.appliesToInput.value = this.appliesToChecks.join(',');
                }
            },

            // Scope of Work
            selectedScopeOfWork: '{{ old('scope_of_work_id', $application->scope_of_work_id ?? '') }}',
            scopeOfWorks: @json($scopeOfWorks),

            get showScopeDetails() {
                if (!this.selectedScopeOfWork) return false;
                let selected = this.scopeOfWorks.find(s => String(s.id) === String(this.selectedScopeOfWork));
                return selected && selected.name !== 'New Construction';
            },

            // Electrical permit toggle
            includeElectrical: {{ old('include_electrical', $application->include_electrical ?? false) ? 'true' : 'false' }},

            // Cost auto-calculation
            costs: {
                building_cost: {{ old('building_cost', $application->building_cost ?? 0) ?: 0 }},
                electrical_cost: {{ old('electrical_cost', $application->electrical_cost ?? 0) ?: 0 }},
                mechanical_cost: {{ old('mechanical_cost', $application->mechanical_cost ?? 0) ?: 0 }},
                electronics_cost: {{ old('electronics_cost', $application->electronics_cost ?? 0) ?: 0 }},
                plumbing_cost: {{ old('plumbing_cost', $application->plumbing_cost ?? 0) ?: 0 }},
                other_equipment_cost: {{ old('other_equipment_cost', $application->other_equipment_cost ?? 0) ?: 0 }},
                equipment_cost_1: {{ old('equipment_cost_1', $application->equipment_cost_1 ?? 0) ?: 0 }},
                equipment_cost_2: {{ old('equipment_cost_2', $application->equipment_cost_2 ?? 0) ?: 0 }},
                equipment_cost_3: {{ old('equipment_cost_3', $application->equipment_cost_3 ?? 0) ?: 0 }},
            },

            get totalEstimatedCost() {
                let sum = 0;
                for (let key in this.costs) {
                    sum += parseFloat(this.costs[key]) || 0;
                }
                return sum.toFixed(2);
            },
        }
    }

    function validateOccupancy() {
        var checked = document.querySelectorAll('input[name="occupancy_sub_groups[]"]:checked');
        var errorEl = document.getElementById('occupancy-error');
        if (checked.length === 0) {
            errorEl.classList.remove('hidden');
            errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        errorEl.classList.add('hidden');
        return true;
    }

    function copyPeeToSew(checked) {
        var fields = [
            ['pee_name', 'sew_name'],
            ['pee_prc_no', 'sew_prc_no'],
            ['pee_prc_validity', 'sew_prc_validity'],
            ['pee_date_signed', 'sew_date_signed'],
            ['pee_ptr_no', 'sew_ptr_no'],
            ['pee_ptr_date_issued', 'sew_ptr_date_issued'],
            ['pee_ptr_issued_at', 'sew_ptr_issued_at'],
            ['pee_address', 'sew_address'],
            ['pee_tin', 'sew_tin'],
        ];
        for (var i = 0; i < fields.length; i++) {
            var src = document.getElementById(fields[i][0]);
            var dst = document.getElementById(fields[i][1]);
            if (src && dst) {
                dst.value = checked ? src.value : '';
                dst.readOnly = checked;
                dst.style.backgroundColor = checked ? '#f3f4f6' : '';
            }
        }
        var prof = document.getElementById('sew_profession');
        if (prof) {
            prof.disabled = checked;
            prof.style.backgroundColor = checked ? '#f3f4f6' : '';
        }
    }
</script>
@endpush
