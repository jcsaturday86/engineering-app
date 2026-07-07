<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permit Verification — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="flex items-center justify-center w-16 h-16 {{ $permit ? 'bg-green-600' : 'bg-red-500' }} rounded-2xl shadow-lg">
                    <i class="fas {{ $permit ? 'fa-circle-check' : 'fa-circle-xmark' }} text-white text-2xl"></i>
                </div>
            </div>
            <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-gray-900">
                Permit Verification
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-xl rounded-xl sm:px-10 border border-gray-100">
                @if($permit)
                    <div class="flex items-center gap-2 mb-5 px-3 py-2 bg-green-50 border border-green-200 rounded-lg">
                        <i class="fas fa-shield-halved text-green-600"></i>
                        <span class="text-sm font-semibold text-green-700">Verified — record found in EPMS</span>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Permit Type</dt>
                            <dd class="text-gray-900 font-semibold">{{ $permit->permitType->code === 'OP' ? 'Certificate of Occupancy' : 'Building Permit' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Permit No.</dt>
                            <dd class="text-gray-900 font-mono font-semibold">{{ $permit->permit_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Date Issued</dt>
                            <dd class="text-gray-900">{{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('F d, Y') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Issued By</dt>
                            <dd class="text-gray-900">{{ trim(($permit->building_official_title ?? '') . ' ' . ($permit->building_official_name ?? '')) ?: '—' }}</dd>
                            @if($permit->building_official_designation)
                                <dd class="text-gray-500 text-xs">{{ $permit->building_official_designation }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Status</dt>
                            <dd class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $application->status ?? '')) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Applicant</dt>
                            <dd class="text-gray-900">{{ $applicantName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Project Title</dt>
                            <dd class="text-gray-900">{{ $application->project_title ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Location</dt>
                            <dd class="text-gray-900">{{ $application->building_street ?? '—' }}{{ $application->buildingBarangay ? ', ' . $application->buildingBarangay->name : '' }}</dd>
                        </div>
                    </dl>

                    <p class="mt-6 text-xs text-gray-400 text-center">
                        This confirms the permit's existence in official EPMS records only. It does not substitute for the original signed permit document.
                    </p>
                @else
                    <div class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                        <i class="fas fa-triangle-exclamation text-red-600"></i>
                        <span class="text-sm font-semibold text-red-700">This permit could not be verified</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 text-center">
                        The verification code is invalid or does not match any record in our system.
                    </p>
                @endif
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} Engineering Permit Management System
            </p>
        </div>
    </div>
</body>
</html>
