@extends('layouts.app')

@section('title', 'Upload Requirements')

@section('breadcrumbs')
    <a href="{{ route('online.dashboard') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Upload Requirements</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-2">{{ $application->application_number }}</h3>
        <p class="text-sm text-gray-500">{{ $application->project_title }}</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Uploaded Requirements</h3>
        @if($requirements->count())
        <div class="space-y-3">
            @foreach($requirements as $req)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-file text-gray-400"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $req->requirement_name }}</p>
                        <p class="text-xs text-gray-500">{{ $req->original_filename }}</p>
                    </div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full
                    @if($req->status === 'approved') bg-green-100 text-green-700
                    @elseif($req->status === 'rejected') bg-red-100 text-red-700
                    @else bg-yellow-100 text-yellow-700 @endif">
                    {{ ucfirst($req->status) }}
                </span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400">No requirements uploaded yet.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Upload New Requirement</h3>
        <form method="POST" action="{{ route('online.upload.store', $application) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Requirement Type</label>
                <select name="requirement_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">-- Select --</option>
                    <option>Structural Plans</option>
                    <option>Architectural Plans</option>
                    <option>Electrical Plans</option>
                    <option>Mechanical Plans</option>
                    <option>Sanitary/Plumbing Plans</option>
                    <option>Fire Safety Plans</option>
                    <option>Lot Plan</option>
                    <option>Vicinity Map</option>
                    <option>Geodetic Survey</option>
                    <option>Soil Analysis</option>
                    <option>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">File (PDF, JPG, PNG — max 10MB)</label>
                <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                <i class="fas fa-upload mr-1"></i> Upload
            </button>
        </form>
    </div>
</div>
@endsection
