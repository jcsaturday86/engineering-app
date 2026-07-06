<?php

namespace App\Http\Controllers;

use App\Models\Permit;

class VerifyController extends Controller
{
    public function show(string $token)
    {
        $permit = Permit::where('verification_token', $token)
            ->with('applicationable', 'permitType')
            ->first();

        if (! $permit) {
            return view('verify.permit', ['permit' => null]);
        }

        $application = $permit->applicationable;

        $applicantName = trim(
            ($application->applicant_last_name ?? '') . ', ' .
            ($application->applicant_first_name ?? '') . ' ' .
            ($application->applicant_middle_name ?? '') . ' ' .
            ($application->applicant_suffix ?? '')
        );
        $applicantName = preg_replace('/\s+/', ' ', $applicantName);

        return view('verify.permit', [
            'permit' => $permit,
            'application' => $application,
            'applicantName' => $applicantName,
        ]);
    }
}
