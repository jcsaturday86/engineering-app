@extends('layouts.app')

@section('title', $user ? 'Edit User' : 'Create User')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.users') }}" class="text-gray-500 hover:text-gray-700">Users</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $user ? 'Edit' : 'Create' }}</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">{{ $user ? 'Edit User' : 'Create User' }}</h2>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <form method="POST" action="{{ $user ? route('settings.users.update', $user) : route('settings.users.store') }}" autocomplete="off" x-data="{
            pw: '',
            pwConfirm: '',
            get strength() {
                let s = 0;
                if (this.pw.length >= 8) s++;
                if (/[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw)) s++;
                if (/[0-9]/.test(this.pw)) s++;
                if (/[^A-Za-z0-9]/.test(this.pw)) s++;
                return s;
            },
            get strengthLabel() {
                return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.strength];
            },
            get strengthColor() {
                return ['', 'bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'][this.strength];
            },
            get matched() {
                return this.pwConfirm.length > 0 && this.pw === this.pwConfirm;
            },
            get notMatched() {
                return this.pwConfirm.length > 0 && this.pw !== this.pwConfirm;
            }
        }">
            @csrf
            @if($user)
                @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                {{-- Name fields --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" id="first_name" name="first_name"
                            value="{{ old('first_name', $user?->first_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        @error('first_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name"
                            value="{{ old('middle_name', $user?->middle_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('middle_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" id="last_name" name="last_name"
                            value="{{ old('last_name', $user?->last_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        @error('last_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Contact fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email"
                            value="{{ old('email', $user?->email) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" id="phone" name="phone"
                            value="{{ old('phone', $user?->phone) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Department & Position --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <input type="text" id="department" name="department"
                            value="{{ old('department', $user?->department) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('department')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input type="text" id="position" name="position"
                            value="{{ old('position', $user?->position) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('position')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select a role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role', $user?->roles?->first()?->id) == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password (create only) --}}
                @if(!$user)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password" name="password"
                                @input="pw = $event.target.value"
                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        {{-- Password strength bar --}}
                        <div x-show="pw.length > 0" x-cloak class="mt-2">
                            <div class="flex gap-1">
                                <template x-for="i in 4">
                                    <div class="h-1 flex-1 rounded-full transition-all" :class="i <= strength ? strengthColor : 'bg-gray-200'"></div>
                                </template>
                            </div>
                            <p class="text-xs mt-1 font-medium" :class="{
                                'text-red-500': strength === 1,
                                'text-yellow-600': strength === 2,
                                'text-blue-600': strength === 3,
                                'text-green-600': strength === 4
                            }" x-text="strengthLabel"></p>
                            {{-- Complexity requirements checklist --}}
                            <div class="mt-2 space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas text-[10px]" :class="pw.length >= 8 ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300'"></i>
                                    <span class="text-xs" :class="pw.length >= 8 ? 'text-green-600' : 'text-gray-400'">At least 8 characters</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fas text-[10px]" :class="/[A-Z]/.test(pw) ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300'"></i>
                                    <span class="text-xs" :class="/[A-Z]/.test(pw) ? 'text-green-600' : 'text-gray-400'">One uppercase letter (A-Z)</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fas text-[10px]" :class="/[a-z]/.test(pw) ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300'"></i>
                                    <span class="text-xs" :class="/[a-z]/.test(pw) ? 'text-green-600' : 'text-gray-400'">One lowercase letter (a-z)</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fas text-[10px]" :class="/[0-9]/.test(pw) ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300'"></i>
                                    <span class="text-xs" :class="/[0-9]/.test(pw) ? 'text-green-600' : 'text-gray-400'">One number (0-9)</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="fas text-[10px]" :class="/[^A-Za-z0-9]/.test(pw) ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-300'"></i>
                                    <span class="text-xs" :class="/[^A-Za-z0-9]/.test(pw) ? 'text-green-600' : 'text-gray-400'">One special character (!@#$%&*)</span>
                                </div>
                            </div>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                @input="pwConfirm = $event.target.value"
                                class="w-full px-3 py-2 pr-10 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-blue-500"
                                :class="notMatched ? 'border-red-300 focus:ring-red-500' : matched ? 'border-green-300 focus:ring-green-500' : 'border-gray-300 focus:ring-blue-500'"
                                required>
                            <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        <p x-show="matched" x-cloak class="mt-1 text-xs text-green-600"><i class="fas fa-check-circle mr-0.5"></i> Passwords match</p>
                        <p x-show="notMatched" x-cloak class="mt-1 text-xs text-red-500"><i class="fas fa-times-circle mr-0.5"></i> Passwords do not match</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="{{ route('settings.users') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save"></i> {{ $user ? 'Update User' : 'Create User' }}
                </button>
            </div>
        </form>
    </div>
</div>

@if(!$user)
<script>
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
    input.focus();
}
</script>
@endif
@endsection
