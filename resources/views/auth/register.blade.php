@extends('layouts.guest')

@section('title', 'Register')
@section('subtitle', 'Create your account to apply for permits online')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{
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

    {{-- Honeypot (hidden from humans, bots fill it) --}}
    <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true" tabindex="-1">
        <label for="website">Website</label>
        <input type="text" name="website" id="website" value="" autocomplete="off" tabindex="-1">
        <label for="full_name">Full Name</label>
        <input type="text" name="full_name" id="full_name" value="" autocomplete="off" tabindex="-1">
    </div>

    {{-- Name --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400 text-sm"></i>
                    </div>
                    <input id="first_name" name="first_name" type="text" required value="{{ old('first_name') }}"
                        class="block w-full pl-10 pr-3 py-2.5 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('first_name') border-red-300 @else border-gray-300 @enderror"
                        placeholder="First name">
                </div>
                @error('first_name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <input id="last_name" name="last_name" type="text" required value="{{ old('last_name') }}"
                    class="block w-full px-3 py-2.5 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('last_name') border-red-300 @else border-gray-300 @enderror"
                    placeholder="Last name">
                @error('last_name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400 text-sm"></i>
            </div>
            <input id="email" name="email" type="email" required value="{{ old('email') }}"
                class="block w-full pl-10 pr-3 py-2.5 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @else border-gray-300 @enderror"
                placeholder="you@example.com">
        </div>
        @error('email')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @else
            <p class="mt-1 text-xs text-gray-400">We'll send permit updates to this email</p>
        @enderror
    </div>

    {{-- Phone --}}
    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Contact Number</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-phone text-gray-400 text-sm"></i>
            </div>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="09xx-xxx-xxxx">
        </div>
    </div>

    {{-- Password --}}
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400 text-sm"></i>
            </div>
            <input id="password" name="password" type="password" required
                x-model="pw"
                class="block w-full pl-10 pr-12 py-2.5 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-300 @else border-gray-300 @enderror"
                placeholder="Create a strong password">
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

    {{-- Confirm Password --}}
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400 text-sm"></i>
            </div>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                x-model="pwConfirm"
                class="block w-full pl-10 pr-20 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Re-enter your password">
            <div class="absolute inset-y-0 right-0 px-3 flex items-center z-20">
                <button type="button" onclick="togglePassword('password_confirmation', this)" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
        </div>
        <p x-show="notMatched" x-cloak class="mt-1 text-xs text-red-500">Passwords do not match</p>
    </div>

    {{-- Math CAPTCHA --}}
    @php
        $a = rand(2, 9);
        $b = rand(1, 9);
        $captchaAnswer = $a + $b;
    @endphp
    <div>
        <label for="captcha" class="block text-sm font-medium text-gray-700 mb-1.5">Security Check <span class="text-red-500">*</span></label>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg select-none">
                <i class="fas fa-shield-alt text-blue-500 text-sm"></i>
                <span class="text-sm font-mono font-bold text-gray-700">{{ $a }} + {{ $b }} = ?</span>
            </div>
            <input type="number" name="captcha" id="captcha" required
                class="block w-24 px-3 py-2.5 border rounded-lg text-sm text-center font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('captcha') border-red-300 @else border-gray-300 @enderror"
                placeholder="Answer">
            <input type="hidden" name="captcha_answer" value="{{ encrypt($captchaAnswer) }}">
        </div>
        @error('captcha')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Data Privacy Agreement --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4" x-data="{ showFull: false }">
        <div class="flex items-start gap-3">
            <input type="checkbox" name="privacy_agreement" id="privacy_agreement" required value="1"
                class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                {{ old('privacy_agreement') ? 'checked' : '' }}>
            <div class="text-xs text-gray-600 leading-relaxed">
                <label for="privacy_agreement" class="font-medium text-gray-700 cursor-pointer">
                    I agree to the Data Privacy Policy <span class="text-red-500">*</span>
                </label>
                <p class="mt-1">
                    By registering, I consent to the collection, processing, and storage of my personal information
                    in accordance with <strong>Republic Act No. 10173</strong> (Data Privacy Act of 2012).
                    <button type="button" @click="showFull = !showFull" class="text-blue-600 hover:text-blue-800 font-medium underline ml-1">
                        <span x-text="showFull ? 'Show less' : 'Read more'"></span>
                    </button>
                </p>
                <div x-show="showFull" x-cloak class="mt-3 space-y-2 text-xs text-gray-500 border-t border-gray-200 pt-3">
                    <p><strong class="text-gray-700">Purpose:</strong> Your personal information will be collected and processed solely for engineering permit applications.</p>
                    <p><strong class="text-gray-700">Information Collected:</strong> Full name, contact number, email address, and other information required for permit processing as mandated by the National Building Code of the Philippines.</p>
                    <p><strong class="text-gray-700">Data Security:</strong> Your personal data will be stored securely with appropriate organizational, technical, and physical security measures.</p>
                    <p><strong class="text-gray-700">Data Sharing:</strong> Your information may be shared with other government offices involved in permit processing, strictly for official purposes.</p>
                    <p><strong class="text-gray-700">Your Rights:</strong> Under RA 10173, you have the right to be informed, to access, to object, to erasure or blocking, to rectification, to data portability, and to file a complaint with the National Privacy Commission.</p>
                </div>
            </div>
        </div>
        @error('privacy_agreement')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
        <i class="fas fa-user-plus"></i> Create Account
    </button>
</form>

<div class="mt-6 text-center">
    <p class="text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
    </p>
</div>

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
@endsection
