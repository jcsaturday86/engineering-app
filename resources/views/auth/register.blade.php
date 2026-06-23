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
