@extends('layouts.app')

@section('title', 'My Profile')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Profile</span>
@endsection

@section('content')
@php $user = auth()->user(); @endphp

<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">My Profile</h2>
    </div>

    {{-- Profile Information --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Profile Information</h3>
        </div>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-6">
                {{-- Name fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" id="first_name" name="first_name"
                            value="{{ old('first_name', $user->first_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        @error('first_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name"
                            value="{{ old('middle_name', $user->middle_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('middle_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" id="last_name" name="last_name"
                            value="{{ old('last_name', $user->last_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        @error('last_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                        <input type="text" id="suffix" name="suffix"
                            value="{{ old('suffix', $user->suffix) }}"
                            placeholder="Jr., Sr., III, etc."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('suffix')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Contact fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email"
                            value="{{ $user->email }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed"
                            readonly>
                        <p class="mt-1 text-xs text-gray-500">Email cannot be changed. Contact an administrator.</p>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" id="phone" name="phone"
                            value="{{ old('phone', $user->phone) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Save --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save"></i> Save Profile
                </button>
            </div>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Password</h3>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">Ensure your account is using a strong password to stay secure.</p>
            <a href="{{ route('password.change') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-lock"></i> Change Password
            </a>
        </div>
    </div>
</div>
@endsection
