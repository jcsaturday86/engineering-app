<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Login — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg shadow-blue-600/30">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
            </div>
            <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">
                Staff Portal
            </h2>
            <p class="mt-1 text-center text-sm text-gray-400">
                Engineering Permit Management System
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-gray-800 py-8 px-6 shadow-2xl rounded-xl sm:px-10 border border-gray-700">
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded-lg">
                        <ul class="text-sm text-red-300 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('status'))
                    <div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded-lg">
                        <p class="text-sm text-green-300">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('staff.login.submit') }}" class="space-y-5" autocomplete="off">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300">Email address</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-shield text-gray-500 text-sm"></i>
                            </div>
                            <input id="email" name="email" type="email" required
                                value="{{ old('email') }}"
                                class="block w-full pl-10 pr-3 py-2.5 bg-gray-700 border border-gray-600 rounded-lg text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="staff@epms.local">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-500 text-sm"></i>
                            </div>
                            <input id="password" name="password" type="password" required
                                class="block w-full pl-10 pr-12 py-2.5 bg-gray-700 border border-gray-600 rounded-lg text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your password">
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center z-20 text-gray-500 hover:text-gray-300">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="h-4 w-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-400">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-blue-500 transition">
                        <i class="fas fa-sign-in-alt"></i> Sign in
                    </button>
                </form>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-300">
                    <i class="fas fa-arrow-left text-xs mr-1"></i> Back to public login
                </a>
            </div>

            <p class="mt-4 text-center text-xs text-gray-600">
                <i class="fas fa-lock text-xs mr-1"></i> Authorized personnel only
            </p>
        </div>
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
</body>
</html>
