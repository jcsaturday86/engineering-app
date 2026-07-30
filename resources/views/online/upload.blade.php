@extends('layouts.app')

@section('title', 'Upload Requirements')

@section('breadcrumbs')
    <a href="{{ route('online.applications') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Upload Requirements</span>
@endsection

@section('content')
@php
    $canUpload = in_array($application->status, ['draft', 'returned'], true);

    // Flattened uploadable rows, so progress counts include nested items.
    $uploadableItems = $checklist->flatMap(fn ($r) => collect([$r])->concat($r->children))
        ->filter(fn ($r) => $r->is_uploadable);
    $mandatoryItems = $uploadableItems->where('requirement_level', 'mandatory');
    $mandatoryDone = $mandatoryItems->filter(fn ($r) => isset($uploadsByRequirement[$r->id]))->count();
    $totalUploaded = $uploadableItems->filter(fn ($r) => isset($uploadsByRequirement[$r->id]))->count();
@endphp
<div class="space-y-6">

    @if($canUpload)
        @include('partials.client-wizard-stepper', [
            'wizardStep' => \App\Support\ClientWizard::currentStep($application, $applicationType),
        ])
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ $application->application_number }}</h3>
            @if($application->project_title ?? null)
            <p class="text-sm text-gray-500 mt-0.5">{{ $application->project_title }}</p>
            @endif
        </div>
        @if($canUpload)
        <form method="POST" action="{{ route('online.submit', ['type' => $applicationType, 'id' => $application->id]) }}" onsubmit="return confirm('Submit this application for Engineering review? You won\'t be able to edit it until it is reviewed.');">
            @csrf
            <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                <i class="fas fa-paper-plane mr-1"></i> Submit for Review
            </button>
        </form>
        @endif
    </div>

    @if(session('error'))
    <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
        <i class="fas fa-circle-exclamation text-red-500 mt-0.5"></i>
        <p class="text-sm text-red-800">{{ session('error') }}</p>
    </div>
    @endif

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
        class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
        <i class="fas fa-check-circle text-green-500"></i>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($checklist->isEmpty())
        {{-- Data-driven, not type-specific: any service with nothing configured lands here. --}}
        <div class="bg-white rounded-xl border border-gray-200 p-10 text-center">
            <i class="fas fa-circle-check text-4xl text-green-400 mb-4"></i>
            <h3 class="text-base font-semibold text-gray-900">No documents are required for this application</h3>
            <p class="text-sm text-gray-500 mt-1.5 max-w-md mx-auto">
                This service has no document requirements, so there is nothing to attach.
                @if($canUpload)
                You can submit it for Engineering review right away.
                @endif
            </p>
        </div>
    @else
        {{-- Progress --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                <p class="text-sm font-semibold text-gray-900">Required Documents</p>
                <div class="flex items-center gap-3 text-xs">
                    <span class="{{ $mandatoryDone === $mandatoryItems->count() ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                        <i class="fas {{ $mandatoryDone === $mandatoryItems->count() ? 'fa-circle-check' : 'fa-circle-exclamation' }} mr-1"></i>
                        {{ $mandatoryDone }} of {{ $mandatoryItems->count() }} mandatory uploaded
                    </span>
                    <span class="text-gray-400">·</span>
                    <span class="text-gray-500">{{ $totalUploaded }} of {{ $uploadableItems->count() }} total</span>
                </div>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full {{ $mandatoryDone === $mandatoryItems->count() ? 'bg-green-500' : 'bg-blue-500' }} transition-all"
                     style="width: {{ $mandatoryItems->count() > 0 ? round($mandatoryDone / $mandatoryItems->count() * 100) : 100 }}%"></div>
            </div>
            @if($canUpload && $mandatoryDone < $mandatoryItems->count())
            <p class="text-xs text-gray-500 mt-3">
                All <strong>mandatory</strong> documents must be uploaded before you can submit.
                Conditional and optional documents are only needed if their stated condition applies to you.
            </p>
            @endif
            <p class="text-xs text-gray-400 mt-2">Accepted formats: PDF, JPG, PNG — up to 10 MB each.</p>
        </div>

        {{-- Checklist --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            @include('partials.requirements-checklist', [
                'checklist' => $checklist,
                'uploadsByRequirement' => $uploadsByRequirement,
                'application' => $application,
                'applicationType' => $applicationType,
                'canUpload' => $canUpload,
            ])
        </div>
    @endif

    {{-- Uploads made before the checklist existed, or otherwise unmatched. --}}
    @if($legacyUploads->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Other Uploaded Documents</h3>
        <div class="space-y-2">
            @foreach($legacyUploads as $upload)
            <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-lg flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <i class="fas fa-file text-gray-400 shrink-0"></i>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $upload->requirement_name }}</p>
                        <a href="{{ route('requirements.view', $upload) }}" target="_blank"
                           class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $upload->original_filename }}
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs px-2 py-1 rounded-full
                        @if($upload->status === 'approved') bg-green-100 text-green-700
                        @elseif($upload->status === 'rejected') bg-red-100 text-red-700
                        @else bg-yellow-100 text-yellow-700 @endif">
                        {{ ucfirst($upload->status) }}
                    </span>
                    <a href="{{ route('requirements.view', $upload) }}" target="_blank" title="View document"
                       class="px-2.5 py-1.5 rounded-lg border border-blue-200 text-xs text-blue-700 hover:bg-blue-50">
                        <i class="fas fa-eye"></i>
                    </a>
                    @if($canUpload)
                    <form method="POST" action="{{ route('online.upload.destroy', ['type' => $applicationType, 'id' => $application->id, 'requirementId' => $upload->id]) }}"
                        onsubmit="return confirm('Permanently delete this document? The file will be removed from the server.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Delete permanently"
                            class="px-2.5 py-1.5 rounded-lg border border-red-200 text-xs text-red-600 hover:bg-red-50">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
