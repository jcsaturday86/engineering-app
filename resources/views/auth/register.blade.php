@extends('layouts.guest')

@section('title', 'Register')
@section('subtitle', 'Create your account to apply for permits online')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{
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
    get strengthLabel() { return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.strength]; },
    get strengthColor() { return ['', 'bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'][this.strength]; },
    get matched() { return this.pwConfirm.length > 0 && this.pw === this.pwConfirm; },
    get notMatched() { return this.pwConfirm.length > 0 && this.pw !== this.pwConfirm; }
}">
    @csrf

    {{-- Honeypot --}}
    <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true" tabindex="-1">
        <input type="text" name="website" value="" autocomplete="off" tabindex="-1">
        <input type="text" name="full_name" value="" autocomplete="off" tabindex="-1">
    </div>

    {{-- Step 1: Personal Info --}}
    <div class="border border-gray-200 rounded-lg p-4 space-y-3">
        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider"><i class="fas fa-user mr-1"></i> Personal Information</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <input id="first_name" name="first_name" type="text" required value="{{ old('first_name') }}"
                    class="block w-full px-3 py-2 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('first_name') border-red-300 @else border-gray-300 @enderror"
                    placeholder="First name *">
                @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <input id="last_name" name="last_name" type="text" required value="{{ old('last_name') }}"
                    class="block w-full px-3 py-2 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('last_name') border-red-300 @else border-gray-300 @enderror"
                    placeholder="Last name *">
                @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-envelope text-gray-400 text-xs"></i></div>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                    class="block w-full pl-9 pr-3 py-2 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @else border-gray-300 @enderror"
                    placeholder="Email address *">
            </div>
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @else <p class="mt-0.5 text-[11px] text-gray-400">Permit updates will be sent here</p>
            @enderror
        </div>

        <div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-phone text-gray-400 text-xs"></i></div>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                    class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Contact number (optional)">
            </div>
        </div>
    </div>

    {{-- Step 2: Password --}}
    <div class="border border-gray-200 rounded-lg p-4 space-y-3">
        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider"><i class="fas fa-lock mr-1"></i> Create Password</p>

        {{-- Password field --}}
        <div>
            <div class="relative">
                <input id="password" name="password" type="password" required
                    @input="pw = $event.target.value"
                    class="block w-full px-3 pr-10 py-2 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-300 @else border-gray-300 @enderror"
                    placeholder="Password *">
                <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>

            {{-- Strength bar + checklist --}}
            <div x-show="pw.length > 0" x-cloak class="mt-2">
                <div class="flex gap-1 mb-1">
                    <template x-for="i in 4">
                        <div class="h-1 flex-1 rounded-full transition-all" :class="i <= strength ? strengthColor : 'bg-gray-200'"></div>
                    </template>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-0.5">
                    <div class="flex items-center gap-1">
                        <i class="fas text-[9px]" :class="pw.length >= 8 ? 'fa-check text-green-500' : 'fa-times text-gray-300'"></i>
                        <span class="text-[11px]" :class="pw.length >= 8 ? 'text-green-600' : 'text-gray-400'">8+ characters</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <i class="fas text-[9px]" :class="/[A-Z]/.test(pw) ? 'fa-check text-green-500' : 'fa-times text-gray-300'"></i>
                        <span class="text-[11px]" :class="/[A-Z]/.test(pw) ? 'text-green-600' : 'text-gray-400'">Uppercase (A-Z)</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <i class="fas text-[9px]" :class="/[a-z]/.test(pw) ? 'fa-check text-green-500' : 'fa-times text-gray-300'"></i>
                        <span class="text-[11px]" :class="/[a-z]/.test(pw) ? 'text-green-600' : 'text-gray-400'">Lowercase (a-z)</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <i class="fas text-[9px]" :class="/[0-9]/.test(pw) ? 'fa-check text-green-500' : 'fa-times text-gray-300'"></i>
                        <span class="text-[11px]" :class="/[0-9]/.test(pw) ? 'text-green-600' : 'text-gray-400'">Number (0-9)</span>
                    </div>
                    <div class="flex items-center gap-1 col-span-2">
                        <i class="fas text-[9px]" :class="/[^A-Za-z0-9]/.test(pw) ? 'fa-check text-green-500' : 'fa-times text-gray-300'"></i>
                        <span class="text-[11px]" :class="/[^A-Za-z0-9]/.test(pw) ? 'text-green-600' : 'text-gray-400'">Special character (!@#$%&*)</span>
                    </div>
                </div>
            </div>
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Confirm Password field --}}
        <div>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    @input="pwConfirm = $event.target.value"
                    class="block w-full px-3 pr-10 py-2 border rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:border-blue-500"
                    :class="notMatched ? 'border-red-300 focus:ring-red-500' : matched ? 'border-green-300 focus:ring-green-500' : 'border-gray-300 focus:ring-blue-500'"
                    placeholder="Confirm password *">
                <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
            <p x-show="matched" x-cloak class="mt-0.5 text-[11px] text-green-600"><i class="fas fa-check-circle mr-0.5"></i> Passwords match</p>
            <p x-show="notMatched" x-cloak class="mt-0.5 text-[11px] text-red-500"><i class="fas fa-times-circle mr-0.5"></i> Passwords do not match</p>
        </div>
    </div>

    {{-- Step 3: Security --}}
    <div class="border border-gray-200 rounded-lg p-4 space-y-3">
        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider"><i class="fas fa-shield-alt mr-1"></i> Security</p>

        {{-- CAPTCHA --}}
        @php $a = rand(2, 9); $b = rand(1, 9); @endphp
        <div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 border border-gray-200 rounded-lg select-none">
                    <span class="text-sm font-mono font-bold text-gray-700">{{ $a }} + {{ $b }} = ?</span>
                </div>
                <input type="number" name="captcha" id="captcha" required
                    class="block w-20 px-3 py-2 border rounded-lg text-sm text-center font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('captcha') border-red-300 @else border-gray-300 @enderror"
                    placeholder="?">
                <input type="hidden" name="captcha_answer" value="{{ encrypt($a + $b) }}">
                <span class="text-[11px] text-gray-400">Prove you're human</span>
            </div>
            @error('captcha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Privacy --}}
        <div class="flex items-start gap-2.5 pt-1">
            <input type="checkbox" name="privacy_agreement" id="privacy_agreement" required value="1"
                class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                {{ old('privacy_agreement') ? 'checked' : '' }}>
            <div class="text-[11px] text-gray-500 leading-relaxed" x-data="{ open: false }">
                <label for="privacy_agreement" class="text-xs font-medium text-gray-700 cursor-pointer">
                    I agree to the <button type="button" @click="open = !open" class="text-blue-600 underline hover:text-blue-800">Data Privacy Policy</button> <span class="text-red-500">*</span>
                </label>
                <span class="text-gray-400">(RA 10173)</span>
                <div x-show="open" x-cloak class="mt-2 p-3 bg-white border border-gray-200 rounded-lg space-y-1.5 text-[11px] text-gray-500">
                    <p><strong class="text-gray-700">Purpose:</strong> Collected solely for engineering permit applications.</p>
                    <p><strong class="text-gray-700">Data Collected:</strong> Name, contact, email, and permit-related information.</p>
                    <p><strong class="text-gray-700">Security:</strong> Stored securely with appropriate technical measures.</p>
                    <p><strong class="text-gray-700">Sharing:</strong> Shared only with government offices for permit processing.</p>
                    <p><strong class="text-gray-700">Your Rights:</strong> Access, correction, erasure, portability, and complaint to NPC.</p>
                </div>
            </div>
        </div>
        @error('privacy_agreement') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
        <i class="fas fa-user-plus"></i> Create Account
    </button>
</form>

<div class="mt-4 text-center">
    <p class="text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
    </p>
</div>

<script>
function togglePassword(id, btn) {
    var el = document.getElementById(id);
    var ic = btn.querySelector('i');
    if (el.type === 'password') {
        el.type = 'text';
        ic.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        el.type = 'password';
        ic.classList.replace('fa-eye-slash', 'fa-eye');
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.focus();
}
</script>
@endsection
