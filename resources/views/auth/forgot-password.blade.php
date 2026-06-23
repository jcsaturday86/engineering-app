@extends('layouts.guest')

@section('title', 'Forgot Password')
@section('subtitle', 'Enter your email to reset your password')

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
        <div class="mt-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400 text-sm"></i>
            </div>
            <input id="email" name="email" type="email" autocomplete="email" required
                value="{{ old('email') }}"
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="you@example.com">
        </div>
    </div>

    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
        Send Reset Link
    </button>
</form>

<div class="mt-4 text-center">
    <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
        <i class="fas fa-arrow-left text-xs mr-1"></i> Back to login
    </a>
</div>
@endsection
