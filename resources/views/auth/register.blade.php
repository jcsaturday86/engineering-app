@extends('layouts.guest')

@section('title', 'Register')
@section('subtitle', 'Create your account to apply online')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
            <input id="first_name" name="first_name" type="text" required value="{{ old('first_name') }}"
                class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
            <input id="last_name" name="last_name" type="text" required value="{{ old('last_name') }}"
                class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
        <input id="email" name="email" type="email" required value="{{ old('email') }}"
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="you@example.com">
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">Contact Number</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="09xx-xxx-xxxx">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input id="password" name="password" type="password" required
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Minimum 8 characters">
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                    in accordance with <strong>Republic Act No. 10173</strong> (Data Privacy Act of 2012) of the Philippines.
                    <button type="button" @click="showFull = !showFull" class="text-blue-600 hover:text-blue-800 font-medium underline ml-1">
                        <span x-text="showFull ? 'Show less' : 'Read more'"></span>
                    </button>
                </p>
                <div x-show="showFull" x-cloak class="mt-3 space-y-2 text-xs text-gray-500 border-t border-gray-200 pt-3">
                    <p><strong class="text-gray-700">Purpose of Data Collection:</strong> Your personal information will be collected and processed solely for the purpose of processing your engineering permit application, including but not limited to building permits, occupancy permits, and other related services.</p>
                    <p><strong class="text-gray-700">Information Collected:</strong> Full name, contact number, email address, mailing address, government-issued identification numbers, and other information required for permit processing as mandated by the National Building Code of the Philippines.</p>
                    <p><strong class="text-gray-700">Data Storage &amp; Security:</strong> Your personal data will be stored securely within the Engineering Permit Management System. Appropriate organizational, technical, and physical security measures are in place to protect your data against unauthorized access, disclosure, alteration, or destruction.</p>
                    <p><strong class="text-gray-700">Data Sharing:</strong> Your information may be shared with other government offices involved in the permit processing workflow, including the City Planning and Development Office and the City Treasurer's Office, strictly for official purposes.</p>
                    <p><strong class="text-gray-700">Data Retention:</strong> Your personal information will be retained for the duration required by law and applicable government regulations regarding public records and building permits.</p>
                    <p><strong class="text-gray-700">Your Rights:</strong> Under RA 10173, you have the right to be informed, to access, to object, to erasure or blocking, to rectification, to data portability, and to file a complaint with the National Privacy Commission. You may exercise these rights by contacting the City Engineering Office.</p>
                </div>
            </div>
        </div>
        @error('privacy_agreement')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
        Create Account
    </button>
</form>

<div class="mt-6 text-center">
    <p class="text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
    </p>
</div>
@endsection
