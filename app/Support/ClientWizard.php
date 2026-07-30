<?php

namespace App\Support;

use App\Models\DocumentRequirement;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the online client's three-step submission journey.
 *
 * Distinct from ApplicationTimeline, which tracks the permit's progress through
 * the office after submission. This one covers only what the applicant still has
 * to do: fill in the form, attach the required documents, hand it to Engineering.
 *
 * Used by resources/views/partials/client-wizard-stepper.blade.php and by
 * OnlineApplicationController::submit() — the same missingMandatory() call backs
 * both the progress indicator and the submit gate, so they cannot disagree.
 */
class ClientWizard
{
    /**
     * @var array<int, array{label:string,short:string}>
     */
    public const STEPS = [
        ['label' => 'Application Details', 'short' => 'Details'],
        ['label' => 'Upload Requirements', 'short' => 'Documents'],
        ['label' => 'Submit for Review', 'short' => 'Submit'],
    ];

    /**
     * Names of mandatory documents this application has not yet attached.
     *
     * A permit type with no mandatory requirements configured returns an empty
     * collection, so it is never blocked — that is how Annual Inspection passes
     * today without being special-cased.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $application
     * @return Collection<int, string>
     */
    public static function missingMandatory($application, string $type): Collection
    {
        $mandatory = DocumentRequirement::query()
            ->active()
            ->uploadable()
            ->mandatory()
            ->whereHas('permitType', fn ($q) => $q->where('code', strtoupper($type)))
            ->orderBy('sort_order')
            ->get();

        if ($mandatory->isEmpty()) {
            return collect();
        }

        $uploadedIds = $application->applicationRequirements()
            ->whereNotNull('document_requirement_id')
            ->pluck('document_requirement_id');

        return $mandatory->whereNotIn('id', $uploadedIds)->pluck('name')->values();
    }

    /**
     * Which of the three steps the client is currently on.
     *
     * A null application means it has not been saved yet, so they are still on
     * step 1. Once saved, they sit on step 2 until every mandatory document is
     * attached, at which point step 3 (submitting) is all that is left.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $application
     */
    public static function currentStep($application, string $type): int
    {
        if (! $application) {
            return 1;
        }

        return self::missingMandatory($application, $type)->isEmpty() ? 3 : 2;
    }
}
