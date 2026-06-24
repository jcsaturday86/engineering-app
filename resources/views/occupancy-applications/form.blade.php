@extends('layouts.app')

@section('title', $application ? 'Edit OP Application' : 'New Occupancy Permit Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('occupancy-applications.index') }}" class="text-gray-500 hover:text-gray-700">Occupancy Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">
        {{ $application ? 'Edit ' . $application->application_number : 'New Occupancy Permit Application' }}
    </span>
@endsection

@section('content')
@php
    $isOP = true;

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

    // Section counter
    $sectionNum = 0;
@endphp

<form
    method="POST"
    action="{{ $application ? route('occupancy-applications.update', $application) : route('occupancy-applications.store') }}"
    x-data="applicationForm()"
    onsubmit="return validateOccupancy();"
>
    @csrf
    @if($application)
        @method('PUT')
    @endif

    <div class="space-y-4">
        {{-- Compact Form Header --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-md bg-indigo-600 text-white">OP</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $application ? 'Edit Application' : 'New Occupancy Permit Application' }}
                    </h2>
                    @if($application)
                        <p class="text-xs text-gray-500">{{ $application->application_number }}</p>
                    @endif
                </div>
            </div>
            <p class="text-xs text-gray-400">Fields marked with <span class="text-red-500">*</span> are required</p>
        </div>

        {{-- ================================================================== --}}
        {{-- 1. OCCUPANCY PERMIT APPLICATION --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Occupancy Permit Application
            </h3>

            {{-- Application Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Application Type <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    @foreach($applicationTypes as $type)
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="application_type_id" value="{{ $type->id }}"
                                class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                {{ old('application_type_id', $application->application_type_id ?? '') == $type->id ? 'checked' : '' }} required>
                            <span class="text-sm text-gray-700">{{ $type->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('application_type_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- BP No, BP Date, FSEC No, FSEC Date --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="bp_number" class="block text-xs font-medium text-gray-600 mb-1">Building Permit No.</label>
                    <input type="text" name="bp_number" id="bp_number"
                        value="{{ old('bp_number', $application->bp_number ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('bp_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="bp_issued_date" class="block text-xs font-medium text-gray-600 mb-1">Date Issued</label>
                    <input type="date" name="bp_issued_date" id="bp_issued_date"
                        value="{{ old('bp_issued_date', optional($application->bp_issued_date ?? null)->format('Y-m-d') ?? ($application->bp_issued_date ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('bp_issued_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="fsec_no" class="block text-xs font-medium text-gray-600 mb-1">FSEC No.</label>
                    <input type="text" name="fsec_no" id="fsec_no"
                        value="{{ old('fsec_no', $application->fsec_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('fsec_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="fsec_issued_date" class="block text-xs font-medium text-gray-600 mb-1">Date Issued</label>
                    <input type="date" name="fsec_issued_date" id="fsec_issued_date"
                        value="{{ old('fsec_issued_date', optional($application->fsec_issued_date ?? null)->format('Y-m-d') ?? ($application->fsec_issued_date ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('fsec_issued_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Applies For --}}
            <div>
                <label for="applies_for" class="block text-xs font-medium text-gray-600 mb-1">Applies For (FSIC)</label>
                <input type="text" name="applies_for" id="applies_for"
                    value="{{ old('applies_for', $application->applies_for ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @error('applies_for') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 2. APPLICANT INFORMATION --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
            </h3>

            {{-- Name row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_first_name" class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_first_name" id="applicant_first_name"
                        value="{{ old('applicant_first_name', $application->applicant_first_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_first_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_middle_name" class="block text-xs font-medium text-gray-600 mb-1">Middle Name</label>
                    <input type="text" name="applicant_middle_name" id="applicant_middle_name"
                        value="{{ old('applicant_middle_name', $application->applicant_middle_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_middle_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_last_name" class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_last_name" id="applicant_last_name"
                        value="{{ old('applicant_last_name', $application->applicant_last_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_last_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_suffix" class="block text-xs font-medium text-gray-600 mb-1">Suffix</label>
                    <input type="text" name="applicant_suffix" id="applicant_suffix"
                        value="{{ old('applicant_suffix', $application->applicant_suffix ?? '') }}"
                        placeholder="Jr., Sr., III"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_suffix')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Contact, Email --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="applicant_contact_no" class="block text-xs font-medium text-gray-600 mb-1">Contact No. <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_contact_no" required id="applicant_contact_no"
                        value="{{ old('applicant_contact_no', $application->applicant_contact_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_contact_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_email" class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="applicant_email" id="applicant_email"
                        value="{{ old('applicant_email', $application->applicant_email ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 3. APPLICANT ADDRESS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
            </h3>

            {{-- Province / City / Barangay cascading --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="applicant_province_id" class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-500">*</span></label>
                    <select name="applicant_province_id" required id="applicant_province_id" x-model="selectedProvince"
                        @change="selectedCity = ''; selectedBarangay = '';"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        @change="selectedBarangay = '';"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_street')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_zip_code" class="block text-xs font-medium text-gray-600 mb-1">Zip Code</label>
                    <input type="text" name="applicant_zip_code" id="applicant_zip_code"
                        value="{{ old('applicant_zip_code', $application->applicant_zip_code ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_zip_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 4. PROJECT DETAILS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Project Details
            </h3>

            {{-- Name of Project & Completion Date --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label for="project_title" class="block text-xs font-medium text-gray-600 mb-1">Name of Project <span class="text-red-500">*</span></label>
                    <input type="text" name="project_title" id="project_title"
                        value="{{ old('project_title', $application->project_title ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('project_title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="completion_date" class="block text-xs font-medium text-gray-600 mb-1">Date of Completion <span class="text-red-500">*</span></label>
                    <input type="date" name="completion_date" id="completion_date"
                        value="{{ old('completion_date', optional($application->completion_date ?? null)->format('Y-m-d') ?? ($application->completion_date ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('completion_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Building Location: Street, Barangay, City --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="building_street" class="block text-xs font-medium text-gray-600 mb-1">Street <span class="text-red-500">*</span></label>
                    <input type="text" name="building_street" id="building_street"
                        value="{{ old('building_street', $application->building_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('building_street') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="building_barangay_id" class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="building_barangay_id" id="building_barangay_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Select --</option>
                        @foreach($sfcBarangays as $brgy)
                            <option value="{{ $brgy->id }}"
                                {{ old('building_barangay_id', $application->building_barangay_id ?? '') == $brgy->id ? 'selected' : '' }}>
                                {{ $brgy->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('building_barangay_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">City / Municipality</label>
                    <input type="text" value="City of San Fernando, La Union" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600">
                </div>
            </div>

            {{-- Storeys, Units, Floor Area --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="no_of_storeys" class="block text-xs font-medium text-gray-600 mb-1">No. of Storey/s <span class="text-red-500">*</span></label>
                    <input type="number" name="no_of_storeys" id="no_of_storeys" min="0"
                        value="{{ old('no_of_storeys', $application->no_of_storeys ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('no_of_storeys') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="no_of_units" class="block text-xs font-medium text-gray-600 mb-1">No. of Units <span class="text-red-500">*</span></label>
                    <input type="number" name="no_of_units" id="no_of_units" min="0"
                        value="{{ old('no_of_units', $application->no_of_units ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('no_of_units') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="total_floor_area" class="block text-xs font-medium text-gray-600 mb-1">Total Gross Floor Area (SQ.M.) <span class="text-red-500">*</span></label>
                    <input type="number" name="total_floor_area" id="total_floor_area" min="0" step="any"
                        value="{{ old('total_floor_area', $application->total_floor_area ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                    @error('total_floor_area') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 5. CHARACTER OF OCCUPANCY --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Use or Character of Occupancy <span class="text-red-500">*</span>
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
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
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
                                                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
        {{-- 6. OWNER INFORMATION --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Owner Information
            </h3>

            {{-- Name & Address --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="owner_name" class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                    <input type="text" name="owner_name" id="owner_name"
                        value="{{ old('owner_name', $application->owner_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('owner_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_address" class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                    <input type="text" name="owner_address" id="owner_address"
                        value="{{ old('owner_address', $application->owner_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('owner_date_signed')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_govt_id" class="block text-xs font-medium text-gray-600 mb-1">Gov't Issued ID No.</label>
                    <input type="text" name="owner_govt_id" id="owner_govt_id"
                        value="{{ old('owner_govt_id', $application->owner_govt_id ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('owner_govt_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_id_date_issued" class="block text-xs font-medium text-gray-600 mb-1">ID Date Issued</label>
                    <input type="date" name="owner_id_date_issued" id="owner_id_date_issued"
                        value="{{ old('owner_id_date_issued', optional($application->owner_id_date_issued ?? null)->format('Y-m-d') ?? ($application->owner_id_date_issued ?? '')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('owner_id_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_id_place_issued" class="block text-xs font-medium text-gray-600 mb-1">Place Issued</label>
                    <input type="text" name="owner_id_place_issued" id="owner_id_place_issued"
                        value="{{ old('owner_id_place_issued', $application->owner_id_place_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('owner_id_place_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 7. REMARKS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Remarks
            </h3>
            <div>
                <label for="remarks" class="block text-xs font-medium text-gray-600 mb-1">Remarks</label>
                <input type="text" name="remarks" id="remarks"
                    value="{{ old('remarks', $application->remarks ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @error('remarks')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
        <a href="{{ route('occupancy-applications.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-times text-xs"></i> Cancel
        </a>
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
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
</script>
@endpush
