@extends('layouts.guest')

@section('title', 'Change Password')
@section('subtitle', 'Update your password')

@section('content')
<form method="POST" action="{{ route('password.change.update') }}" class="space-y-5" autocomplete="off">
    @csrf

    <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
        <div class="mt-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400 text-sm"></i>
            </div>
            <input id="current_password" name="current_password" type="password" required
                class="block w-full pl-10 pr-12 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter current password">
            <button type="button" onclick="togglePassword('current_password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                <i class="fas fa-eye text-sm"></i>
            </button>
        </div>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
        <div class="mt-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400 text-sm"></i>
            </div>
            <input id="password" name="password" type="password" required
                class="block w-full pl-10 pr-12 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Minimum 8 characters">
            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                <i class="fas fa-eye text-sm"></i>
            </button>
        </div>
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
        <div class="mt-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400 text-sm"></i>
            </div>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                class="block w-full pl-10 pr-12 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Confirm new password">
            <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-400 hover:text-gray-600">
                <i class="fas fa-eye text-sm"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
        Change Password
    </button>
</form>

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
