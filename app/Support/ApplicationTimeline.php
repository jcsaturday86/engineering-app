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

    /**
     * Who processed each step, derived from the model's own Spatie activity
     * log rather than a dedicated column — every status change is already
     * recorded there (via logAll()->logOnlyDirty() on the model) together
     * with the authenticated causer, so this needs no schema change.
     *
     * Later activity for the same status overwrites earlier ones, so a
     * step that was reached more than once (e.g. reverted and redone)
     * reports the most recent occurrence.
     *
     * @return array<string, array{user: ?\App\Models\User, at: ?\Illuminate\Support\Carbon}>
     */
    public static function processedBy($application): array
    {
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', $application->getMorphClass())
            ->where('subject_id', $application->id)
            ->where('created_at', '>=', $application->created_at)
            ->whereIn('event', ['created', 'updated'])
            ->with('causer')
            ->orderBy('id')
            ->get();

        $map = [];

        foreach ($activities as $activity) {
            $newStatus = $activity->properties['attributes']['status'] ?? null;

            if ($newStatus === null) {
                continue;
            }

            $map[$newStatus] = [
                'user' => $activity->causer,
                'at' => $activity->created_at,
            ];
        }

        return $map;
    }

    /**
     * Human-readable role label for who performed a step: the staff
     * member's position/role, or "Applicant" when the causer is the
     * client who owns the application.
     */
    public static function processedByLabel($application, ?\App\Models\User $user): string
    {
        if (! $user) {
            return 'System';
        }

        if ($application->client_user_id && $user->id === $application->client_user_id) {
            return 'Applicant';
        }

        return $user->position ?: (ucwords(str_replace('-', ' ', $user->getRoleNames()->first() ?? '')) ?: 'Staff');
    }

    /**
     * Full chronological event log, every status change the application
     * has ever gone through — including disapproval/resubmission cycles
     * that the linear progress stepper in build() collapses away (since
     * it only shows the *current* returned state, if any). Sourced from
     * the same activity log as processedBy(), so no schema change.
     *
     * This is the "full transparency" audit trail: a BP application that
     * was returned three times and resubmitted three times before being
     * approved shows all six of those events here, in order, each with
     * the reviewer's remarks and who acted.
     *
     * @return array<int, array{from: ?string, to: string, label: string, remarks: ?string, user: ?\App\Models\User, at: ?\Illuminate\Support\Carbon}>
     */
    public static function fullHistory($application): array
    {
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', $application->getMorphClass())
            ->where('subject_id', $application->id)
            ->where('created_at', '>=', $application->created_at)
            ->whereIn('event', ['created', 'updated'])
            ->with('causer')
            ->orderBy('id')
            ->get();

        $history = [];

        foreach ($activities as $activity) {
            $newStatus = $activity->properties['attributes']['status'] ?? null;

            if ($newStatus === null) {
                continue;
            }

            $oldStatus = $activity->properties['old']['status'] ?? null;

            $history[] = [
                'from' => $oldStatus,
                'to' => $newStatus,
                'label' => self::eventLabel($oldStatus, $newStatus),
                'remarks' => $newStatus === 'returned' ? ($activity->properties['attributes']['review_remarks'] ?? null) : null,
                'user' => $activity->causer,
                'at' => $activity->created_at,
            ];
        }

        return $history;
    }

    /**
     * Describes a single status transition in plain language, distinguishing
     * a disapproval and its resubmission from the generic status label.
     */
    protected static function eventLabel(?string $from, string $to): string
    {
        if ($to === 'returned') {
            return 'Returned for Revision';
        }

        if ($from === 'returned' && $to === 'pending_approval') {
            return 'Resubmitted by Applicant';
        }

        if ($from !== null && $to === 'draft') {
            return 'Reverted to Draft';
        }

        return \App\Enums\ApplicationStatus::tryFrom($to)?->label() ?? ucfirst(str_replace('_', ' ', $to));
    }
}
