{{--
    The uploaded-documents list on an application detail page.

    Expects $application in scope. Shared verbatim by all 6 show.blade.php views
    and by both portals — the view link is authorized per-record in
    ApplicationRequirementController, so staff see any document and a client
    sees only their own.
--}}
<h3 class="text-sm font-semibold text-gray-900 mb-3">Requirements ({{ $application->applicationRequirements->count() }})</h3>
@forelse($application->applicationRequirements as $req)
<div class="flex items-start justify-between gap-3 py-2 border-b border-gray-100 last:border-0">
    <div class="min-w-0">
        <p class="text-sm text-gray-700">{{ $req->requirement_name }}</p>
        @if($req->original_filename)
        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $req->original_filename }}</p>
        @endif
        @if($req->status === 'rejected' && $req->reviewer_remarks)
        <p class="text-xs text-red-600 mt-0.5">{{ $req->reviewer_remarks }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2 shrink-0">
        @php
            $applicationApproved = ! in_array($application->status, ['draft', 'pending_approval', 'returned', 'cancelled'], true);
        @endphp
        @if(! ($req->status === 'pending' && $applicationApproved))
        <span class="text-xs px-2 py-0.5 rounded-full
            @if($req->status === 'approved') bg-green-100 text-green-700
            @elseif($req->status === 'rejected') bg-red-100 text-red-700
            @else bg-yellow-100 text-yellow-700 @endif">{{ ucfirst($req->status) }}</span>
        @endif
        <a href="{{ route('requirements.view', $req) }}" target="_blank" title="View document"
           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-blue-200 text-xs font-medium text-blue-700 hover:bg-blue-50 transition">
            <i class="fas fa-eye"></i> View
        </a>
    </div>
</div>
@empty
<p class="text-sm text-gray-400">No requirements uploaded.</p>
@endforelse
