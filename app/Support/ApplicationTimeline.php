<?php

namespace App\Support;

/**
 * Single source of truth for the workflow step sequence shown to applicants.
 *
 * Used by the horizontal stepper on every application detail page
 * (resources/views/partials/application-stepper.blade.php) and by the
 * vertical timeline on the client Track page (resources/views/online/track.blade.php).
 */
class ApplicationTimeline
{
    /**
     * Build the ordered step list for an application.
     *
     * BP is the only permit type that routes through Zoning; the other five
     * (OP/FP/DP/SGP/AI) go straight from Engineering approval to assessment.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $application
     * @return array<int, array{status:string,label:string,short:string,date:?\Illuminate\Support\Carbon}>
     */
    public static function build($application, string $type): array
    {
        $usesZoning = strtoupper($type) === 'BP';

        $timeline = [
            ['status' => 'draft', 'label' => 'Application Created', 'short' => 'Created', 'date' => $application->created_at],
        ];

        if ($application->status === 'returned' || $application->review_remarks) {
            $timeline[] = ['status' => 'returned', 'label' => 'Returned for Revision', 'short' => 'Returned', 'date' => $application->updated_at];
        }

        $timeline[] = ['status' => 'pending_approval', 'label' => 'Submitted — Awaiting Engineering Approval', 'short' => 'Submitted', 'date' => $application->submitted_at];

        if ($usesZoning) {
            $timeline[] = ['status' => 'for_zoning_assessment', 'label' => 'For Zoning Assessment', 'short' => 'Zoning', 'date' => $application->approved_at];
            $timeline[] = ['status' => 'zoning_assessed', 'label' => 'Zoning Assessed', 'short' => 'Zoning Done', 'date' => null];
        } else {
            $timeline[] = ['status' => 'submitted', 'label' => 'Approved — Routed to Engineering Assessment', 'short' => 'Approved', 'date' => $application->approved_at];
        }

        return array_merge($timeline, [
            ['status' => 'engineering_assessed', 'label' => 'Engineering Assessed', 'short' => 'Assessed', 'date' => $application->assessed_at],
            ['status' => 'billed', 'label' => 'Billed', 'short' => 'Billed', 'date' => null],
            ['status' => 'paid', 'label' => 'Payment Received', 'short' => 'Paid', 'date' => $application->paid_at],
            ['status' => 'permit_generated', 'label' => 'Permit Generated', 'short' => 'Permit', 'date' => null],
            ['status' => 'released', 'label' => 'Released', 'short' => 'Released', 'date' => $application->released_at],
        ]);
    }

    /**
     * Index of the step matching the current status, or null when the status
     * sits outside the timeline entirely (e.g. 'cancelled').
     */
    public static function currentIndex(array $timeline, string $status): ?int
    {
        $index = array_search($status, array_column($timeline, 'status'), true);

        return $index === false ? null : $index;
    }

    /**
     * Fill percentage (0-100) for the connecting progress line.
     */
    public static function percent(array $timeline, string $status): int
    {
        $index = self::currentIndex($timeline, $status);
        $steps = count($timeline);

        if ($index === null || $steps < 2) {
            return 0;
        }

        return (int) round($index / ($steps - 1) * 100);
    }
}
