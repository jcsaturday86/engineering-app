@php
    $currentRoute = request()->route()?->getName() ?? '';
    $currentType = request()->get('type', '');
    $currentUrl = request()->url();
@endphp

{{-- Online Portal (Client) --}}
@can('online-apply')
@unless(auth()->user()->can('view-applications'))
<a href="{{ route('online.dashboard') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition {{ str_starts_with($currentRoute, 'online.dashboard') ? 'active' : 'text-gray-700' }}">
    <i class="fas fa-home w-5 text-center"></i>
    <span x-show="sidebarOpen || mobileMenuOpen">My Dashboard</span>
</a>

<div x-data="{ open: {{ str_starts_with($currentRoute, 'online') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-file-alt w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">My Applications</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('online.apply') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'online.apply' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            New Application
        </a>
        <a href="{{ route('online.dashboard') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'online.dashboard' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            All Applications
        </a>
    </div>
</div>
@endunless
@endcan

{{-- Dashboard (Staff/Admin) --}}
@can('view-applications')
<a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition {{ str_starts_with($currentRoute, 'dashboard') ? 'active' : 'text-gray-700' }}">
    <i class="fas fa-tachometer-alt w-5 text-center"></i>
    <span x-show="sidebarOpen || mobileMenuOpen">Dashboard</span>
</a>
@endcan

{{-- Building Permit Applications (Staff/Admin) --}}
@canany(['view-applications', 'create-applications'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'applications') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-building w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Building Permit</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        @can('view-applications')
        <a href="{{ route('applications.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'applications.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            All Applications
        </a>
        @endcan
        @can('create-applications')
        <a href="{{ route('applications.create') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'applications.create' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            New Application
        </a>
        @endcan
    </div>
</div>
@endcanany

{{-- Occupancy Permit Applications (Staff/Admin) --}}
@canany(['view-applications', 'create-applications'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'occupancy-applications') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-door-open w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Occupancy Permit</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        @can('view-applications')
        <a href="{{ route('occupancy-applications.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'occupancy-applications.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            All Applications
        </a>
        @endcan
        @can('create-applications')
        <a href="{{ route('occupancy-applications.create') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'occupancy-applications.create' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            New Application
        </a>
        @endcan
    </div>
</div>
@endcanany

{{-- Fencing Permit Applications (Staff/Admin) --}}
@canany(['view-applications', 'create-applications'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'fencing-applications') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-border-all w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Fencing Permit</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        @can('view-applications')
        <a href="{{ route('fencing-applications.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'fencing-applications.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            All Applications
        </a>
        @endcan
        @can('create-applications')
        <a href="{{ route('fencing-applications.create') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'fencing-applications.create' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            New Application
        </a>
        @endcan
    </div>
</div>
@endcanany

{{-- Demolition Permit Applications (Staff/Admin) --}}
@canany(['view-applications', 'create-applications'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'demolition-applications') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-house-crack w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Demolition Permit</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        @can('view-applications')
        <a href="{{ route('demolition-applications.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'demolition-applications.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            All Applications
        </a>
        @endcan
        @can('create-applications')
        <a href="{{ route('demolition-applications.create') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'demolition-applications.create' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            New Application
        </a>
        @endcan
    </div>
</div>
@endcanany

{{-- Signage Permit Applications (Staff/Admin) --}}
@canany(['view-applications', 'create-applications'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'signage-applications') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-sign w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Signage Permit</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        @can('view-applications')
        <a href="{{ route('signage-applications.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'signage-applications.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            All Applications
        </a>
        @endcan
        @can('create-applications')
        <a href="{{ route('signage-applications.create') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'signage-applications.create' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            New Application
        </a>
        @endcan
    </div>
</div>
@endcanany

{{-- Zoning Assessment --}}
@canany(['view-zoning', 'create-zoning'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'zoning') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-map-marked-alt w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Zoning</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('zoning.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'zoning.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Assessment List
        </a>
    </div>
</div>
@endcanany

{{-- Assessment --}}
@canany(['view-assessments', 'create-assessments'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'assessments') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-calculator w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Assessment</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('assessments.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'assessments.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Building Permit
        </a>
        <a href="{{ route('assessments.occupancy') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'assessments.occupancy' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Occupancy Permit
        </a>
        <a href="{{ route('assessments.demolition') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'assessments.demolition' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Demolition Permit
        </a>
        <a href="{{ route('assessments.signage') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'assessments.signage' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Signage Permit
        </a>
        <a href="{{ route('assessments.fencing') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'assessments.fencing' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Fencing Permit
        </a>
    </div>
</div>
@endcanany

{{-- Collections --}}
@canany(['view-collections', 'create-collections'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'collections') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-cash-register w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Collections</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('collections.index') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'collections.index' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Payment List
        </a>
        @can('void-collections')
        <a href="{{ route('collections.void') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'collections.void' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Void Receipt
        </a>
        @endcan
    </div>
</div>
@endcanany

{{-- Permits --}}
@canany(['view-permits', 'generate-permits'])
<div x-data="{ open: {{ str_starts_with($currentRoute, 'permits') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-certificate w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Permits</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('permits.building') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'permits.building' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Building Permits
        </a>
        <a href="{{ route('permits.occupancy') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'permits.occupancy' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Occupancy Permits
        </a>
        <a href="{{ route('permits.demolition') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'permits.demolition' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Demolition Permits
        </a>
        <a href="{{ route('permits.signage') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'permits.signage' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Signage Permits
        </a>
        <a href="{{ route('permits.fencing') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'permits.fencing' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Fencing Permits
        </a>
        <a href="{{ route('permits.zoning') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'permits.zoning' ? 'text-primary-700 bg-primary-50 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            Zoning
        </a>
    </div>
</div>
@endcanany

{{-- Reports --}}
@can('view-reports')
<div x-data="{ open: {{ str_starts_with($currentRoute, 'reports') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-chart-bar w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Reports</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('reports.permits') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Permit Reports</a>
        <a href="{{ route('reports.revenue') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Revenue Reports</a>
        <a href="{{ route('reports.collections') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Collection Reports</a>
        @can('view-audit-logs')
        <a href="{{ route('reports.audit-logs') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Audit Logs</a>
        @endcan
    </div>
</div>
@endcan

{{-- Separator --}}
<hr class="my-3 border-gray-200">

{{-- Settings --}}
@can('manage-settings')
<div x-data="{ open: {{ str_starts_with($currentRoute, 'settings') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg transition text-gray-700">
        <div class="flex items-center gap-3">
            <i class="fas fa-cog w-5 text-center"></i>
            <span x-show="sidebarOpen || mobileMenuOpen">Settings</span>
        </div>
        <i x-show="sidebarOpen || mobileMenuOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-cloak class="ml-8 mt-1 space-y-1">
        <a href="{{ route('settings.index') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">General</a>
        @can('manage-users')
        <a href="{{ route('settings.users') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Users</a>
        @endcan
        @can('manage-roles')
        <a href="{{ route('settings.roles') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Roles</a>
        @endcan
        @can('manage-fee-schedules')
        <a href="{{ route('settings.fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Fee Schedules</a>
        <a href="{{ route('settings.zoning-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Zoning Fees</a>
        <a href="{{ route('settings.mech-insp-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Mech Inspection Fees</a>
        <a href="{{ route('settings.plumbing-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Plumbing Fees</a>
        <a href="{{ route('settings.electronics-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Electronics Fees</a>
        <a href="{{ route('settings.accessory-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Accessory Fees</a>
        <a href="{{ route('settings.acc-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Acc. Misc. Fees</a>
        <a href="{{ route('settings.demolition-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Demolition Fees</a>
        <a href="{{ route('settings.surcharge-fees') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Surcharge Fees</a>
        @endcan
        @can('manage-signatories')
        <a href="{{ route('settings.signatories') }}" class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-50">Signatories</a>
        @endcan
    </div>
</div>
@endcan
