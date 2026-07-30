{{--
    Client-facing document checklist with a per-row upload control.

    Expects:
      $checklist            — two-level tree of active DocumentRequirement rows
      $uploadsByRequirement — ApplicationRequirement keyed by document_requirement_id
      $application, $applicationType
      $canUpload            — whether the application is still editable
--}}
<div class="divide-y divide-gray-100">
    @foreach($checklist as $requirement)
        @foreach(collect([$requirement])->concat($requirement->children) as $item)
        @php
            $isChild = $item->id !== $requirement->id;
            $upload = $uploadsByRequirement[$item->id] ?? null;
        @endphp

        <div class="{{ $isChild ? 'pl-10 sm:pl-14 bg-gray-50/40' : 'px-5' }} py-4 {{ $isChild ? 'pr-5' : '' }}">
            <div class="flex items-start justify-between gap-4 flex-wrap sm:flex-nowrap">

                {{-- Label --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-start gap-2 flex-wrap">
                        @if($upload)
                        <i class="fas fa-circle-check text-green-500 text-sm mt-0.5 shrink-0"></i>
                        @elseif($item->is_uploadable)
                        <i class="far fa-circle text-gray-300 text-sm mt-0.5 shrink-0"></i>
                        @endif

                        <p class="text-sm {{ $item->is_uploadable ? 'text-gray-800' : 'font-semibold text-gray-900' }}">
                            {{ $item->name }}
                        </p>

                        @if($item->is_uploadable)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ $item->levelColor() }}">
                            {{ $item->levelLabel() }}
                        </span>
                        @endif
                    </div>

                    @if($item->condition_note)
                    <p class="text-xs text-gray-500 italic mt-1 ml-6">{{ $item->condition_note }}</p>
                    @endif

                    @if($upload)
                    <div class="flex items-center gap-2 mt-2 ml-6 flex-wrap">
                        <i class="fas fa-file text-gray-400 text-xs"></i>
                        <a href="{{ route('requirements.view', $upload) }}" target="_blank"
                           class="text-xs text-blue-600 hover:text-blue-800 hover:underline"
                           title="Open {{ $upload->original_filename }}">
                            {{ $upload->original_filename }}
                        </a>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @if($upload->status === 'approved') bg-green-100 text-green-700
                            @elseif($upload->status === 'rejected') bg-red-100 text-red-700
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($upload->status) }}
                        </span>
                    </div>
                    @if($upload->status === 'rejected' && $upload->reviewer_remarks)
                    <p class="text-xs text-red-600 mt-1 ml-6">{{ $upload->reviewer_remarks }}</p>
                    @endif
                    @endif
                </div>

                {{-- Actions. View sits outside the $canUpload gate so a client can
                     still open their documents after submitting, when the upload
                     and remove controls are correctly hidden. --}}
                @if($item->is_uploadable && ($canUpload || $upload))
                <div x-data="{ open: false }" class="shrink-0 w-full sm:w-auto">
                    <div x-show="!open" class="flex items-center gap-1.5">
                        @if($upload)
                        <a href="{{ route('requirements.view', $upload) }}" target="_blank" title="View document"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-blue-200 text-xs font-medium text-blue-700 hover:bg-blue-50 transition">
                            <i class="fas fa-eye"></i> View
                        </a>
                        @endif
                        @if($canUpload)
                        <button @click="open = true" type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition
                                {{ $upload ? 'border-gray-200 text-gray-600 hover:bg-gray-50' : 'border-blue-200 text-blue-700 hover:bg-blue-50' }}">
                            <i class="fas {{ $upload ? 'fa-rotate' : 'fa-upload' }}"></i>
                            {{ $upload ? 'Replace' : 'Upload' }}
                        </button>
                        @endif
                        @if($upload && $canUpload)
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

                    @if($canUpload)
                    <form x-show="open" x-cloak method="POST" enctype="multipart/form-data"
                        action="{{ route('online.upload.store', ['type' => $applicationType, 'id' => $application->id]) }}"
                        class="flex items-center gap-1.5 flex-wrap" autocomplete="off">
                        @csrf
                        <input type="hidden" name="document_requirement_id" value="{{ $item->id }}">
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png"
                            class="text-xs border border-gray-300 rounded-lg px-2 py-1.5 max-w-[220px]">
                        <button type="submit" class="px-2.5 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" @click="open = false" class="px-2.5 py-1.5 bg-gray-400 text-white text-xs rounded-lg hover:bg-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    @endforeach
</div>
