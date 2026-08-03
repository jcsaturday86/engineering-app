{{--
    Full chronological activity history — every status change the application
    has gone through, including disapproval/resubmission cycles that the
    linear Application Progress stepper collapses away.

    Client-only (shown for full transparency on the client's own detail
    page). Expects $application and $applicationType in scope, both already
    defined at the top of all 6 show.blade.php views.
--}}
@php
    $fullHistory = \App\Support\ApplicationTimeline::fullHistory($application);
@endphp
@if(count($fullHistory))
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-1">Full Activity History</h3>
    <p class="text-xs text-gray-400 mb-6">Every action taken on this application, including revisions requested and your resubmissions.</p>
    <div class="relative">
        @foreach($fullHistory as $i => $event)
        @php
            $isReturned = $event['to'] === 'returned';
            $isResubmit = $event['label'] === 'Resubmitted by Applicant';
        @endphp
        <div class="flex items-start gap-4 {{ $i < count($fullHistory) - 1 ? 'pb-6' : '' }}">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                    @if($isReturned) bg-red-500 text-white
                    @elseif($isResubmit) bg-amber-500 text-white
                    @else bg-green-500 text-white @endif">
                    @if($isReturned)
                        <i class="fas fa-rotate-left text-xs"></i>
                    @elseif($isResubmit)
                        <i class="fas fa-paper-plane text-xs"></i>
                    @else
                        <i class="fas fa-check text-xs"></i>
                    @endif
                </div>
                @if($i < count($fullHistory) - 1)
                <div class="w-0.5 flex-1 bg-gray-200" style="min-height:20px"></div>
                @endif
            </div>
            <div class="pt-1">
                <p class="text-sm font-medium {{ $isReturned ? 'text-red-700' : ($isResubmit ? 'text-amber-700' : 'text-gray-800') }}">
                    {{ $event['label'] }}
                </p>
                @if($event['at'])
                <p class="text-xs text-gray-400 mt-0.5">{{ $event['at']->format('M d, Y h:i A') }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-0.5">
                    <i class="fas fa-user text-gray-400 mr-1"></i>
                    {{ $event['user']?->full_name ?? 'System' }}
                    <span class="text-gray-400">&middot; {{ \App\Support\ApplicationTimeline::processedByLabel($application, $event['user']) }}</span>
                </p>
                @if($event['remarks'])
                <p class="text-xs text-red-700 bg-red-50 border border-red-100 rounded-lg px-2.5 py-1.5 mt-1.5">
                    <i class="fas fa-comment-dots mr-1"></i> {{ $event['remarks'] }}
                </p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
