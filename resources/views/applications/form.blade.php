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
<form
    method="POST"
    action="{{ $application ? route('applications.update', $application) : route('applications.store') }}"
    x-data="applicationForm()"
>
    @csrf
    @if($application)
        @method('PUT')
    @endif
    <input type="hidden" name="permit_type_id" value="{{ $permitType->id }}">

    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">
                {{ $application ? 'Edit ' . $application->application_number : 'New ' . $permitType->name . ' Application' }}
            </h2>
        </div>

        {{-- 1. Application Type --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-file-alt mr-2 text-gray-400"></i>Application Type
            </h3>
            <div class="flex flex-wrap gap-4">
                @foreach($applicationTypes as $type)
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="application_type_id" value="{{ $type->id }}"
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            {{ old('application_type_id', $application->application_type_id ?? '') == $type->id ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">{{ $type->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('application_type_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- 2. Applicant Information --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-user mr-2 text-gray-400"></i>Applicant Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="applicant_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_first_name" id="applicant_first_name"
                        value="{{ old('applicant_first_name', $application->applicant_first_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_first_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" name="applicant_middle_name" id="applicant_middle_name"
                        value="{{ old('applicant_middle_name', $application->applicant_middle_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_middle_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_last_name" id="applicant_last_name"
                        value="{{ old('applicant_last_name', $application->applicant_last_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_last_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                    <input type="text" name="applicant_suffix" id="applicant_suffix"
                        value="{{ old('applicant_suffix', $application->applicant_suffix ?? '') }}"
                        placeholder="Jr., Sr., III"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_suffix')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="applicant_tin" class="block text-sm font-medium text-gray-700 mb-1">TIN</label>
                    <input type="text" name="applicant_tin" id="applicant_tin"
                        value="{{ old('applicant_tin', $application->applicant_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_tin')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                    <input type="text" name="applicant_contact_no" id="applicant_contact_no"
                        value="{{ old('applicant_contact_no', $application->applicant_contact_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_contact_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="applicant_email" id="applicant_email"
                        value="{{ old('applicant_email', $application->applicant_email ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="applicant_govt_id" class="block text-sm font-medium text-gray-700 mb-1">Government ID</label>
                    <input type="text" name="applicant_govt_id" id="applicant_govt_id"
                        value="{{ old('applicant_govt_id', $application->applicant_govt_id ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_govt_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_id_date_issued" class="block text-sm font-medium text-gray-700 mb-1">ID Date Issued</label>
                    <input type="date" name="applicant_id_date_issued" id="applicant_id_date_issued"
                        value="{{ old('applicant_id_date_issued', $application->applicant_id_date_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_id_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_id_place_issued" class="block text-sm font-medium text-gray-700 mb-1">ID Place Issued</label>
                    <input type="text" name="applicant_id_place_issued" id="applicant_id_place_issued"
                        value="{{ old('applicant_id_place_issued', $application->applicant_id_place_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_id_place_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 3. Address --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Applicant Address
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="applicant_province_id" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                    <select name="applicant_province_id" id="applicant_province_id" x-model="selectedProvince"
                        @change="selectedCity = ''; selectedBarangay = '';"
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
                    <label for="applicant_city_id" class="block text-sm font-medium text-gray-700 mb-1">City/Municipality</label>
                    <select name="applicant_city_id" id="applicant_city_id" x-model="selectedCity"
                        @change="selectedBarangay = '';"
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
                    <label for="applicant_barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                    <select name="applicant_barangay_id" id="applicant_barangay_id" x-model="selectedBarangay"
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
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="applicant_street" class="block text-sm font-medium text-gray-700 mb-1">Street</label>
                    <input type="text" name="applicant_street" id="applicant_street"
                        value="{{ old('applicant_street', $application->applicant_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_street')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="applicant_zip_code" class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                    <input type="text" name="applicant_zip_code" id="applicant_zip_code"
                        value="{{ old('applicant_zip_code', $application->applicant_zip_code ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('applicant_zip_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 4. Enterprise / Ownership --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-building mr-2 text-gray-400"></i>Enterprise / Ownership
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="enterprise_name" class="block text-sm font-medium text-gray-700 mb-1">Enterprise Name</label>
                    <input type="text" name="enterprise_name" id="enterprise_name"
                        value="{{ old('enterprise_name', $application->enterprise_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('enterprise_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="form_of_ownership_id" class="block text-sm font-medium text-gray-700 mb-1">Form of Ownership</label>
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
        </div>

        {{-- 5. Project Details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-project-diagram mr-2 text-gray-400"></i>Project Details
            </h3>
            <div>
                <label for="project_title" class="block text-sm font-medium text-gray-700 mb-1">Project Title</label>
                <input type="text" name="project_title" id="project_title"
                    value="{{ old('project_title', $application->project_title ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('project_title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="scope_of_work_id" class="block text-sm font-medium text-gray-700 mb-1">Scope of Work</label>
                    <select name="scope_of_work_id" id="scope_of_work_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($scopeOfWorks as $scope)
                            <option value="{{ $scope->id }}"
                                {{ old('scope_of_work_id', $application->scope_of_work_id ?? '') == $scope->id ? 'selected' : '' }}>
                                {{ $scope->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('scope_of_work_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="scope_of_work_details" class="block text-sm font-medium text-gray-700 mb-1">Scope of Work Details</label>
                    <textarea name="scope_of_work_details" id="scope_of_work_details" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('scope_of_work_details', $application->scope_of_work_details ?? '') }}</textarea>
                    @error('scope_of_work_details')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 6. Building Specs --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-ruler-combined mr-2 text-gray-400"></i>Building Specifications
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="no_of_storeys" class="block text-sm font-medium text-gray-700 mb-1">No. of Storeys</label>
                    <input type="number" name="no_of_storeys" id="no_of_storeys" min="0"
                        value="{{ old('no_of_storeys', $application->no_of_storeys ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('no_of_storeys')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="no_of_units" class="block text-sm font-medium text-gray-700 mb-1">No. of Units</label>
                    <input type="number" name="no_of_units" id="no_of_units" min="0"
                        value="{{ old('no_of_units', $application->no_of_units ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('no_of_units')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="total_floor_area" class="block text-sm font-medium text-gray-700 mb-1">Total Floor Area (sqm)</label>
                    <input type="number" name="total_floor_area" id="total_floor_area" min="0" step="0.01"
                        value="{{ old('total_floor_area', $application->total_floor_area ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('total_floor_area')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="lot_area" class="block text-sm font-medium text-gray-700 mb-1">Lot Area (sqm)</label>
                    <input type="number" name="lot_area" id="lot_area" min="0" step="0.01"
                        value="{{ old('lot_area', $application->lot_area ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('lot_area')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 7. Cost Estimates --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-calculator mr-2 text-gray-400"></i>Cost Estimates
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="building_cost" class="block text-sm font-medium text-gray-700 mb-1">Building Cost</label>
                    <input type="number" name="building_cost" id="building_cost" min="0" step="0.01"
                        value="{{ old('building_cost', $application->building_cost ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('building_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="electrical_cost" class="block text-sm font-medium text-gray-700 mb-1">Electrical Cost</label>
                    <input type="number" name="electrical_cost" id="electrical_cost" min="0" step="0.01"
                        value="{{ old('electrical_cost', $application->electrical_cost ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('electrical_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="mechanical_cost" class="block text-sm font-medium text-gray-700 mb-1">Mechanical Cost</label>
                    <input type="number" name="mechanical_cost" id="mechanical_cost" min="0" step="0.01"
                        value="{{ old('mechanical_cost', $application->mechanical_cost ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('mechanical_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="electronics_cost" class="block text-sm font-medium text-gray-700 mb-1">Electronics Cost</label>
                    <input type="number" name="electronics_cost" id="electronics_cost" min="0" step="0.01"
                        value="{{ old('electronics_cost', $application->electronics_cost ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('electronics_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="plumbing_cost" class="block text-sm font-medium text-gray-700 mb-1">Plumbing Cost</label>
                    <input type="number" name="plumbing_cost" id="plumbing_cost" min="0" step="0.01"
                        value="{{ old('plumbing_cost', $application->plumbing_cost ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('plumbing_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="other_equipment_cost" class="block text-sm font-medium text-gray-700 mb-1">Other Equipment Cost</label>
                    <input type="number" name="other_equipment_cost" id="other_equipment_cost" min="0" step="0.01"
                        value="{{ old('other_equipment_cost', $application->other_equipment_cost ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('other_equipment_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 8. Building Location --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-map mr-2 text-gray-400"></i>Building / Project Location
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="lot_no" class="block text-sm font-medium text-gray-700 mb-1">Lot No.</label>
                    <input type="text" name="lot_no" id="lot_no"
                        value="{{ old('lot_no', $application->lot_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('lot_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="block_no" class="block text-sm font-medium text-gray-700 mb-1">Block No.</label>
                    <input type="text" name="block_no" id="block_no"
                        value="{{ old('block_no', $application->block_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('block_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tct_no" class="block text-sm font-medium text-gray-700 mb-1">TCT No.</label>
                    <input type="text" name="tct_no" id="tct_no"
                        value="{{ old('tct_no', $application->tct_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('tct_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tax_dec_no" class="block text-sm font-medium text-gray-700 mb-1">Tax Dec. No.</label>
                    <input type="text" name="tax_dec_no" id="tax_dec_no"
                        value="{{ old('tax_dec_no', $application->tax_dec_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('tax_dec_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="land_classification_id" class="block text-sm font-medium text-gray-700 mb-1">Land Classification</label>
                    <select name="land_classification_id" id="land_classification_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($landClassifications as $lc)
                            <option value="{{ $lc->id }}"
                                {{ old('land_classification_id', $application->land_classification_id ?? '') == $lc->id ? 'selected' : '' }}>
                                {{ $lc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('land_classification_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="building_street" class="block text-sm font-medium text-gray-700 mb-1">Street</label>
                    <input type="text" name="building_street" id="building_street"
                        value="{{ old('building_street', $application->building_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('building_street')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="building_barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                    <select name="building_barangay_id" id="building_barangay_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select --</option>
                        @foreach($barangays as $brgy)
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
            </div>
        </div>

        {{-- 9. Timeline --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>Timeline
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="proposed_construction_date" class="block text-sm font-medium text-gray-700 mb-1">Proposed Construction Date</label>
                    <input type="date" name="proposed_construction_date" id="proposed_construction_date"
                        value="{{ old('proposed_construction_date', $application->proposed_construction_date ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('proposed_construction_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="expected_completion_date" class="block text-sm font-medium text-gray-700 mb-1">Expected Completion Date</label>
                    <input type="date" name="expected_completion_date" id="expected_completion_date"
                        value="{{ old('expected_completion_date', $application->expected_completion_date ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('expected_completion_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 10. Engineer / Architect Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-hard-hat mr-2 text-gray-400"></i>Engineer / Architect Information
            </h3>
            <div>
                <label for="engineer_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="engineer_name" id="engineer_name"
                    value="{{ old('engineer_name', $application->engineer_name ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('engineer_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="engineer_prc_no" class="block text-sm font-medium text-gray-700 mb-1">PRC No.</label>
                    <input type="text" name="engineer_prc_no" id="engineer_prc_no"
                        value="{{ old('engineer_prc_no', $application->engineer_prc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_prc_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_prc_validity" class="block text-sm font-medium text-gray-700 mb-1">PRC Validity</label>
                    <input type="date" name="engineer_prc_validity" id="engineer_prc_validity"
                        value="{{ old('engineer_prc_validity', $application->engineer_prc_validity ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_prc_validity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="engineer_ptr_no" class="block text-sm font-medium text-gray-700 mb-1">PTR No.</label>
                    <input type="text" name="engineer_ptr_no" id="engineer_ptr_no"
                        value="{{ old('engineer_ptr_no', $application->engineer_ptr_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_ptr_no')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_ptr_date_issued" class="block text-sm font-medium text-gray-700 mb-1">PTR Date Issued</label>
                    <input type="date" name="engineer_ptr_date_issued" id="engineer_ptr_date_issued"
                        value="{{ old('engineer_ptr_date_issued', $application->engineer_ptr_date_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_ptr_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_ptr_issued_at" class="block text-sm font-medium text-gray-700 mb-1">PTR Issued At</label>
                    <input type="text" name="engineer_ptr_issued_at" id="engineer_ptr_issued_at"
                        value="{{ old('engineer_ptr_issued_at', $application->engineer_ptr_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_ptr_issued_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="engineer_tin" class="block text-sm font-medium text-gray-700 mb-1">TIN</label>
                    <input type="text" name="engineer_tin" id="engineer_tin"
                        value="{{ old('engineer_tin', $application->engineer_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_tin')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="engineer_address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="engineer_address" id="engineer_address"
                        value="{{ old('engineer_address', $application->engineer_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('engineer_address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 11. Owner Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-id-card mr-2 text-gray-400"></i>Owner Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="owner_name" id="owner_name"
                        value="{{ old('owner_name', $application->owner_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="owner_address" id="owner_address"
                        value="{{ old('owner_address', $application->owner_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="owner_govt_id" class="block text-sm font-medium text-gray-700 mb-1">Government ID</label>
                    <input type="text" name="owner_govt_id" id="owner_govt_id"
                        value="{{ old('owner_govt_id', $application->owner_govt_id ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_govt_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="owner_id_date_issued" class="block text-sm font-medium text-gray-700 mb-1">ID Date Issued</label>
                    <input type="date" name="owner_id_date_issued" id="owner_id_date_issued"
                        value="{{ old('owner_id_date_issued', $application->owner_id_date_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('owner_id_date_issued')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 12. Character of Occupancy --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-layer-group mr-2 text-gray-400"></i>Character of Occupancy
            </h3>
            @php
                $selectedSubGroups = [];
                if ($application && $application->applicationOccupancyGroups) {
                    $selectedSubGroups = $application->applicationOccupancyGroups->pluck('occupancy_sub_group_id')->toArray();
                }
                // Merge with old input if present
                if (old('occupancy_sub_groups')) {
                    $selectedSubGroups = old('occupancy_sub_groups');
                }
            @endphp
            <div class="space-y-3">
                @foreach($occupancyGroups as $group)
                    <div x-data="{ open: false }" class="border border-gray-200 rounded-lg">
                        <button type="button" @click="open = !open"
                            class="flex items-center justify-between w-full px-4 py-3 text-left hover:bg-gray-50 rounded-lg transition">
                            <span class="text-sm font-medium text-gray-800">
                                <span class="text-gray-400 mr-1">{{ $group->code }}</span> {{ $group->name }}
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform" :class="open && 'rotate-180'"></i>
                        </button>
                        <div x-show="open" x-cloak class="px-4 pb-3 space-y-2">
                            @foreach($group->subGroups as $subGroup)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="occupancy_sub_groups[]" value="{{ $subGroup->id }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        {{ in_array($subGroup->id, (array) $selectedSubGroups) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700">{{ $subGroup->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('occupancy_sub_groups')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- 13. Electrical Permit --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-data="{ includeElectrical: {{ old('include_electrical', $application->include_electrical ?? false) ? 'true' : 'false' }} }">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-bolt mr-2 text-yellow-500"></i>Electrical Permit
            </h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="include_electrical" value="0">
                <input type="checkbox" name="include_electrical" value="1" x-model="includeElectrical"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Include Electrical Permit</span>
            </label>
            <div x-show="includeElectrical" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label for="total_connected_load" class="block text-sm font-medium text-gray-700 mb-1">Total Connected Load (kW)</label>
                    <input type="number" name="total_connected_load" id="total_connected_load" min="0" step="0.01"
                        value="{{ old('total_connected_load', $application->total_connected_load ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('total_connected_load')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="total_transformer_capacity" class="block text-sm font-medium text-gray-700 mb-1">Total Transformer Capacity (kVA)</label>
                    <input type="number" name="total_transformer_capacity" id="total_transformer_capacity" min="0" step="0.01"
                        value="{{ old('total_transformer_capacity', $application->total_transformer_capacity ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('total_transformer_capacity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="total_generator_capacity" class="block text-sm font-medium text-gray-700 mb-1">Total Generator Capacity (kW)</label>
                    <input type="number" name="total_generator_capacity" id="total_generator_capacity" min="0" step="0.01"
                        value="{{ old('total_generator_capacity', $application->total_generator_capacity ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('total_generator_capacity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- 14. Remarks --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-sticky-note mr-2 text-gray-400"></i>Remarks
            </h3>
            <div>
                <textarea name="remarks" id="remarks" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Additional notes or remarks...">{{ old('remarks', $application->remarks ?? '') }}</textarea>
                @error('remarks')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pb-6">
            <a href="{{ route('applications.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save"></i>
                {{ $application ? 'Update Application' : 'Create Application' }}
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function applicationForm() {
        return {
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
            }
        }
    }
</script>
@endpush
