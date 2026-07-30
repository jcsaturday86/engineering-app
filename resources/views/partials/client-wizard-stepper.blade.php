{{--
    Three-step progress stepper for the online client's submission journey:
    fill in the form, attach the documents, hand it to Engineering.

    Expects $wizardStep (1-3) — usually \App\Support\ClientWizard::currentStep(),
    or a literal 1 on the form pages where nothing is saved yet.

    Deliberately separate from partials/application-stepper.blade.php: that one
    is status-driven and shows the permit's progress through the office *after*
    submission. This shows what the applicant still has to do. Classes are kept
    identical so the two read as one system when both appear on a draft.
--}}
@php
    $wizSteps = \App\Support\ClientWizard::STEPS;
    $wizCount = count($wizSteps);
    $wizCurrent = max(1, min($wizardStep, $wizCount)) - 1;   // 0-based index
    $wizPercent = $wizCount > 1 ? ($wizCurrent / ($wizCount - 1)) * 100 : 100;
@endphp

<div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Your Application Progress</h3>

    {{-- ===== md and up: full horizontal stepper ===== --}}
    <div class="hidden md:block">
        <div class="relative">
            {{-- connecting line, inset half a column so it runs centre-to-centre --}}
            @php
                $wizInset = 50 / $wizCount;
                $wizSpan = 100 - (2 * $wizInset);
            @endphp
            <div class="absolute h-0.5 bg-gray-200" style="top:15px; left:{{ $wizInset }}%; width:{{ $wizSpan }}%"></div>
            <div class="absolute h-0.5 bg-green-500 transition-all" style="top:15px; left:{{ $wizInset }}%; width:{{ $wizSpan * $wizPercent / 100 }}%"></div>

            <div class="relative flex justify-between">
                @foreach($wizSteps as $i => $wizItem)
                    @php
                        $isCurrent = $i === $wizCurrent;
                        $isComplete = $i < $wizCurrent;
                    @endphp
                    <div class="flex flex-col items-center flex-1 min-w-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 border-2 border-white
                            @if($isCurrent) bg-blue-600 text-white ring-2 ring-blue-200
                            @elseif($isComplete) bg-green-500 text-white
                            @else bg-gray-200 text-gray-400 @endif">
                            @if($isCurrent)
                                <i class="fas fa-circle text-xs animate-pulse"></i>
                            @elseif($isComplete)
                                <i class="fas fa-check text-xs"></i>
                            @else
                                <i class="fas fa-circle text-[8px]"></i>
                            @endif
                        </div>
                        <p class="mt-2 text-[11px] leading-tight text-center px-1
                            @if($isCurrent) text-blue-700 font-semibold
                            @elseif($isComplete) text-green-700
                            @else text-gray-400 @endif">
                            {{ $wizItem['short'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-500 text-center">
            Step {{ $wizCurrent + 1 }} of {{ $wizCount }} &mdash;
            <span class="font-medium text-gray-700">{{ $wizSteps[$wizCurrent]['label'] }}</span>
        </p>
    </div>

    {{-- ===== below md: compact bar ===== --}}
    <div class="md:hidden">
        <div class="flex items-baseline justify-between mb-1.5">
            <span class="text-xs font-medium text-gray-700">{{ $wizSteps[$wizCurrent]['label'] }}</span>
            <span class="text-xs text-gray-400 shrink-0 ml-2">Step {{ $wizCurrent + 1 }} of {{ $wizCount }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="h-2 rounded-full bg-green-500 transition-all" style="width:{{ max($wizPercent, 4) }}%"></div>
        </div>
    </div>
</div>
