<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EPMS') — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {"50":"#eff6ff","100":"#dbeafe","200":"#bfdbfe","300":"#93c5fd","400":"#60a5fa","500":"#3b82f6","600":"#2563eb","700":"#1d4ed8","800":"#1e40af","900":"#1e3a8a","950":"#172554"},
                        gov: {"50":"#f0fdf4","100":"#dcfce7","200":"#bbf7d0","500":"#22c55e","700":"#15803d","800":"#166534","900":"#14532d"},
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background-color: rgb(37 99 235); color: white; }
        .sidebar-link:hover:not(.active) { background-color: rgb(239 246 255); }
        @media print { .no-print { display: none !important; } }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="h-full" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">
    <div class="min-h-full">
        {{-- Mobile sidebar backdrop --}}
        <div x-show="mobileMenuOpen" x-cloak class="fixed inset-0 z-40 bg-gray-600/75 lg:hidden" @click="mobileMenuOpen = false"></div>

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-gray-200 transition-all duration-300 no-print hidden lg:flex">
            {{-- Logo --}}
            <div class="flex items-center h-16 px-4 border-b border-gray-200 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-primary-600 rounded-lg shrink-0">
                        <i class="fas fa-building text-white text-lg"></i>
                    </div>
                    <div x-show="sidebarOpen" x-cloak class="overflow-hidden">
                        <h1 class="text-sm font-bold text-gray-900 leading-tight">EPMS</h1>
                        <p class="text-xs text-gray-500 leading-tight">Engineering Permits</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @include('partials.sidebar-nav')
            </nav>

            {{-- Collapse button --}}
            <div class="px-3 py-3 border-t border-gray-200">
                <button @click="sidebarOpen = !sidebarOpen" class="flex items-center justify-center w-full p-2 text-gray-500 rounded-lg hover:bg-gray-100">
                    <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas text-sm"></i>
                </button>
            </div>
        </aside>

        {{-- Mobile sidebar --}}
        <aside x-show="mobileMenuOpen" x-cloak class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 lg:hidden">
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-primary-600 rounded-lg">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-900">EPMS</span>
                </div>
                <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="px-3 py-4 space-y-1 overflow-y-auto">
                @include('partials.sidebar-nav')
            </nav>
        </aside>

        {{-- Main content --}}
        <div :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'" class="transition-all duration-300">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 no-print">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                    <div class="flex items-center gap-4">
                        <button @click="mobileMenuOpen = true" class="text-gray-500 lg:hidden">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <nav class="hidden sm:flex items-center text-sm text-gray-500">
                            @yield('breadcrumbs')
                        </nav>
                    </div>
                    <div class="flex items-center gap-4">
                        {{-- Notifications --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-500 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-bell"></i>
                                @if(auth()->user()?->unreadNotifications?->count() > 0)
                                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                                @endif
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    @php
                                        $notifications = auth()->user()->unreadNotifications->take(10);
                                    @endphp
                                    @forelse($notifications as $notification)
                                        <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                            <p class="text-sm text-gray-900">{{ $notification->data['message'] ?? 'Notification' }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                        </a>
                                    @empty
                                        <p class="px-4 py-3 text-sm text-gray-500">No new notifications</p>
                                    @endforelse
                                </div>
                                @if($notifications->count())
                                    <form method="POST" action="{{ route('notifications.markRead') }}" class="px-4 py-2 border-t border-gray-100">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">Mark all as read</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- User menu --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-semibold text-primary-700">{{ substr(auth()->user()?->first_name ?? 'U', 0, 1) }}{{ substr(auth()->user()?->last_name ?? '', 0, 1) }}</span>
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="text-sm font-medium text-gray-700">{{ auth()->user()?->full_name ?? 'User' }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()?->roles?->first()?->name ?? '' }}</p>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-400 hidden sm:block"></i>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-circle w-4"></i> Profile
                                </a>
                                <a href="{{ route('settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog w-4"></i> Settings
                                </a>
                                <hr class="my-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt w-4"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="p-4 sm:p-6">
                {{-- Flash messages --}}
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                        <button @click="show = false" class="text-green-400 hover:text-green-600"><i class="fas fa-times"></i></button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div x-data="{ show: true }" x-show="show" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            <p class="text-sm text-yellow-700">{{ session('warning') }}</p>
                        </div>
                        <button @click="show = false" class="text-yellow-400 hover:text-yellow-600"><i class="fas fa-times"></i></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                            <p class="text-sm font-medium text-red-700">Please fix the following errors:</p>
                        </div>
                        <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
