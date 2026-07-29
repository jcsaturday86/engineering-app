{{--
    Horizontal progress stepper for application detail pages.

    Expects $application and $applicationType in scope (both are already defined
    at the top of all 6 show.blade.php views). Optionally accepts a pre-built
    $timeline; otherwise it derives one itself, so staff controllers need not
    pass anything.
--}}
@php
    $stepperTimeline = $timeline ?? \App\Support\ApplicationTimeline::build($application, $applicationType);
    $stepperCurrent = \App\Support\ApplicationTimeline::currentIndex($stepperTimeline, $application->status);
    $stepperPercent = \App\Support\ApplicationTimeline::percent($stepperTimeline, $application->status);
    $stepperCount = count($stepperTimeline);
    $stepperPortal = $portal ?? 'staff';
@endphp

<div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
    @if($application->status === 'cancelled')
        {{-- Cancelled sits outside every timeline — show a plain state instead of an all-gray stepper --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center shrink-0">
                <i class="fas fa-ban text-xs"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-red-700">Application Cancelled</p>
                <p class="text-xs text-gray-500">This application is no longer being processed.</p>
            </div>
        </div>
    @else
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Application Progress</h3>
            @if($stepperPortal === 'client')
                <a href="{{ route('online.track', $routeParams) }}" class="text-xs text-blue-600 hover:text-blue-800">
                    <i class="fas fa-clock-rotate-left mr-1"></i> View full history
                </a>
            @endif
        </div>

        {{-- ===== md and up: full horizontal stepper ===== --}}
        <div class="hidden md:block">
            <div class="relative">
                {{-- connecting line, drawn behind the nodes and inset half a column so it
                     starts at the centre of the first node and ends at the centre of the last --}}
                @php
                    $railInset = 50 / $stepperCount;          // half a column, in %
                    $railSpan = 100 - (2 * $railInset);       // centre-to-centre span, in %
                @endphp
                <div class="absolute h-0.5 bg-gray-200" style="top:15px; left:{{ $railInset }}%; width:{{ $railSpan }}%"></div>
                <div class="absolute h-0.5 bg-green-500 transition-all" style="top:15px; left:{{ $railInset }}%; width:{{ $railSpan * $stepperPercent / 100 }}%"></div>

                <div class="relative flex justify-between">
                    @foreach($stepperTimeline as $i => $step)
                        @php
                            $isCurrent = $stepperCurrent !== null && $i === $stepperCurrent;
                            $isComplete = $stepperCurrent !== null && $i < $stepperCurrent;
                            $isReturned = $isCurrent && $application->status === 'returned';
                        @endphp
                        <div class="flex flex-col items-center flex-1 min-w-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 border-2 border-white
                                @if($isReturned) bg-red-500 text-white ring-2 ring-red-200
                                @elseif($isCurrent) bg-blue-600 text-white ring-2 ring-blue-200
                                @elseif($isComplete) bg-green-500 text-white
                                @else bg-gray-200 text-gray-400 @endif">
                                @if($isReturned)
                                    <i class="fas fa-rotate-left text-xs"></i>
                                @elseif($isCurrent)
                                    <i class="fas fa-circle text-xs animate-pulse"></i>
                                @elseif($isComplete)
                                    <i class="fas fa-check text-xs"></i>
                                @else
                                    <i class="fas fa-circle text-[8px]"></i>
                                @endif
                            </div>
                            <p class="mt-2 text-[11px] leading-tight text-center px-1
                                @if($isReturned) text-red-700 font-semibold
                                @elseif($isCurrent) text-blue-700 font-semibold
                                @elseif($isComplete) text-green-700
                                @else text-gray-400 @endif">
                                {{ $step['short'] }}
                            </p>
                            @if($step['date'] ?? null)
                                <p class="hidden lg:block text-[10px] text-gray-400 mt-0.5">{{ $step['date']->format('M d, Y') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if($stepperCurrent !== null)
                <p class="mt-4 text-xs text-gray-500 text-center">
                    Step {{ $stepperCurrent + 1 }} of {{ $stepperCount }} &mdash;
                    <span class="font-medium text-gray-700">{{ $stepperTimeline[$stepperCurrent]['label'] }}</span>
                </p>
            @endif
        </div>

        {{-- ===== below md: compact bar ===== --}}
        <div class="md:hidden">
            @if($stepperCurrent !== null)
                <div class="flex items-baseline justify-between mb-1.5">
                    <span class="text-xs font-medium text-gray-700">{{ $stepperTimeline[$stepperCurrent]['label'] }}</span>
                    <span class="text-xs text-gray-400 shrink-0 ml-2">Step {{ $stepperCurrent + 1 }} of {{ $stepperCount }}</span>
                </div>
            @endif
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full transition-all {{ $application->status === 'returned' ? 'bg-red-500' : 'bg-green-500' }}" style="width:{{ max($stepperPercent, 4) }}%"></div>
            </div>
        </div>
    @endif
</div>
