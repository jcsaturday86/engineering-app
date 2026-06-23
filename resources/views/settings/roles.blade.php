@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Roles</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Roles & Permissions</h2>
    </div>

    {{-- Roles --}}
    <div class="space-y-4">
        @forelse($roles as $role)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg">
                        <i class="fas fa-shield-alt text-blue-600 text-sm"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ $role->name }}</h3>
                </div>
            </div>
            <div class="p-6">
                @if($role->permissions->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($role->permissions as $permission)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">No permissions assigned</p>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <i class="fas fa-shield-alt text-3xl text-gray-300 mb-3"></i>
            <p class="text-gray-400">No roles defined</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
