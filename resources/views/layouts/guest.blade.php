<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
            </div>
            <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-gray-900">
                Engineering Permit<br>Management System
            </h2>
            <p class="mt-1 text-center text-sm text-gray-500">
                @yield('subtitle', 'Sign in to your account')
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-xl rounded-xl sm:px-10 border border-gray-100">
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('status'))
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-600">{{ session('status') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} Engineering Permit Management System
            </p>
        </div>
    </div>
</body>
</html>
