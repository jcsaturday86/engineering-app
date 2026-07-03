<?php

namespace App\Http\Controllers;

use App\Models\Billing;

class BillingController extends Controller
{
    /**
     * Billing generation happens automatically when an assessment is
     * finalized — see App\Services\BillingService::generateFor().
     * This controller only serves the billing statement PDF.
     */
    public function print(Billing $billing)
    {
        $billing->load('applicationable', 'billingItems');

        $application = $billing->applicationable;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.billing-statement', compact('billing', 'application'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("billing_{$billing->billing_number}.pdf");
    }
}
