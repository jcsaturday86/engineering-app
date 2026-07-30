{{--
    One requirement row in the settings editor, with an inline edit form.
    Expects: $requirement, $permitType, $parentOptions, $depth (0 = top level, 1 = nested)
--}}
<div x-data="{ editing: false }" class="{{ $depth > 0 ? 'pl-12 bg-gray-50/50' : 'px-5' }} {{ $requirement->is_active ? '' : 'opacity-60' }}">

    {{-- Display --}}
    <div x-show="!editing" class="flex items-start justify-between gap-4 py-3.5 {{ $depth > 0 ? 'pr-5' : '' }}">
        <div class="min-w-0 flex-1">
            <div class="flex items-start gap-2 flex-wrap">
                @if($depth > 0)
                <i class="fas fa-level-up-alt fa-rotate-90 text-gray-300 text-xs mt-1 shrink-0"></i>
                @endif
                @if(! $requirement->is_uploadable)
                <i class="fas fa-heading text-gray-400 text-xs mt-1 shrink-0" title="Heading only — accepts no upload"></i>
                @endif
                <p class="text-sm {{ $requirement->is_uploadable ? 'text-gray-900' : 'font-semibold text-gray-700' }}">
                    {{ $requirement->name }}
                </p>
                @if($requirement->is_uploadable)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ $requirement->levelColor() }}">
                    {{ $requirement->levelLabel() }}
                </span>
                @endif
                @unless($requirement->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600 shrink-0">Inactive</span>
                @endunless
            </div>
            @if($requirement->condition_note)
            <p class="text-xs text-gray-500 italic mt-1 {{ $depth > 0 ? 'ml-5' : '' }}">{{ $requirement->condition_note }}</p>
            @endif
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <button @click="editing = true" type="button" title="Edit"
                class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 hover:bg-gray-50">
                <i class="fas fa-pencil-alt"></i>
            </button>
            <form method="POST" action="{{ route('settings.document-requirements.destroy', $requirement) }}"
                onsubmit="return confirm('Delete this requirement{{ $requirement->children->count() ? ' and its ' . $requirement->children->count() . ' nested item(s)' : '' }}? Documents clients already uploaded against it are kept.');">
                @csrf
                @method('DELETE')
                <button type="submit" title="Delete"
                    class="px-2.5 py-1.5 rounded-lg border border-red-200 text-xs text-red-600 hover:bg-red-50">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Edit --}}
    <form x-show="editing" x-cloak method="POST" action="{{ route('settings.document-requirements.update', $requirement) }}"
        class="py-4 {{ $depth > 0 ? 'pr-5' : '' }} space-y-3" autocomplete="off">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Document name</label>
            <textarea name="name" rows="2" required maxlength="1000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">{{ $requirement->name }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Condition note</label>
            <input type="text" name="condition_note" maxlength="1000" value="{{ $requirement->condition_note }}"
                placeholder="e.g. In case the applicant is not the registered owner of the lot."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Level</label>
                <select name="requirement_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @foreach(['mandatory' => 'Mandatory', 'conditional' => 'Conditional', 'optional' => 'Optional'] as $value => $label)
                    <option value="{{ $value }}" {{ $requirement->requirement_level === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nest under</label>
                <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">— Top level —</option>
                    @foreach($parentOptions as $option)
                        @continue($option->id === $requirement->id)
                        <option value="{{ $option->id }}" {{ $requirement->parent_id === $option->id ? 'selected' : '' }}>
                            {{ Str::limit($option->name, 50) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Order</label>
                <input type="number" name="sort_order" min="0" value="{{ $requirement->sort_order }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-4 pb-2">
                <label class="flex items-center gap-2 text-xs text-gray-600">
                    <input type="checkbox" name="is_uploadable" value="1" {{ $requirement->is_uploadable ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Accepts upload
                </label>
                <label class="flex items-center gap-2 text-xs text-gray-600">
                    <input type="checkbox" name="is_active" value="1" {{ $requirement->is_active ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Active
                </label>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700">
                <i class="fas fa-check mr-1"></i> Save
            </button>
            <button type="button" @click="editing = false" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">
                Cancel
            </button>
        </div>
    </form>
</div>
