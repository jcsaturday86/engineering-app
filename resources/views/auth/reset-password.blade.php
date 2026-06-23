@extends('layouts.guest')

@section('title', 'Reset Password')
@section('subtitle', 'Enter your new password')

@section('content')
<form method="POST" action="{{ route('password.update') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
        <input id="email" name="email" type="email" required value="{{ old('email', $email ?? '') }}"
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
        <input id="password" name="password" type="password" required
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Minimum 8 characters">
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition">
        Reset Password
    </button>
</form>

<div class="mt-4 text-center">
    <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-500">Back to login</a>
</div>
@endsection
