@extends('layouts.app')

@section('title', 'New Online Application')

@section('breadcrumbs')
    <a href="{{ route('online.dashboard') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">New Application</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <h2 class="text-xl font-bold text-gray-900 mb-6">New Permit Application</h2>

    <form method="POST" action="{{ route('online.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">Permit &amp; Application Type</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Permit Type</label>
                    <select name="permit_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @foreach($permitTypes as $pt)
                        <option value="{{ $pt->id }}" {{ old('permit_type_id') == $pt->id ? 'selected' : '' }}>{{ $pt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Application Type</label>
                    <select name="application_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @foreach($applicationTypes as $at)
                        <option value="{{ $at->id }}" {{ old('application_type_id') == $at->id ? 'selected' : '' }}>{{ $at->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">Applicant Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">First Name *</label>
                    <input type="text" name="applicant_first_name" value="{{ old('applicant_first_name', auth()->user()->first_name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Last Name *</label>
                    <input type="text" name="applicant_last_name" value="{{ old('applicant_last_name', auth()->user()->last_name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Contact No.</label>
                    <input type="text" name="applicant_contact_no" value="{{ old('applicant_contact_no', auth()->user()->phone) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                    <input type="email" name="applicant_email" value="{{ old('applicant_email', auth()->user()->email) }}" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">Project Details</h3>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Project Title</label>
                <input type="text" name="project_title" value="{{ old('project_title') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="e.g. Two-Storey Residential Building">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Scope of Work</label>
                <select name="scope_of_work_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">-- Select --</option>
                    @foreach($scopeOfWorks as $sw)
                    <option value="{{ $sw->id }}" {{ old('scope_of_work_id') == $sw->id ? 'selected' : '' }}>{{ $sw->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">Estimated Cost</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(['building_cost' => 'Building', 'electrical_cost' => 'Electrical', 'mechanical_cost' => 'Mechanical', 'plumbing_cost' => 'Plumbing', 'electronics_cost' => 'Electronics', 'other_equipment_cost' => 'Other Equipment'] as $field => $label)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $label }}</label>
                    <input type="number" name="{{ $field }}" value="{{ old($field, 0) }}" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('online.dashboard') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                Submit Application
            </button>
        </div>
    </form>
</div>
@endsection
