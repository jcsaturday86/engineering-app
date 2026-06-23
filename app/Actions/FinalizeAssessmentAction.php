<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Assessment;
use App\Services\ApplicationService;
use App\Services\AssessmentService;

class FinalizeAssessmentAction
{
    public function __construct(
        protected AssessmentService $assessmentService,
        protected ApplicationService $applicationService,
    ) {}

    /**
     * Finalize an assessment: recalculate total, mark finalized,
     * and transition the application status accordingly.
     */
    public function __invoke(Assessment $assessment, int $userId): Assessment
    {
        $assessment = $this->assessmentService->finalize($assessment, $userId);

        // Determine the appropriate status transition based on assessment type
        $application = $assessment->application;
        $currentStatus = ApplicationStatus::from($application->status);

        $nextStatus = match ($assessment->assessment_type) {
            'zoning' => ApplicationStatus::ZONING_ASSESSED,
            'building', 'occupancy' => ApplicationStatus::ENGINEERING_ASSESSED,
            default => null,
        };

        if ($nextStatus && $currentStatus->canTransitionTo($nextStatus)) {
            $this->applicationService->transitionStatus($application, $nextStatus);
        }

        activity()
            ->performedOn($assessment)
            ->causedBy($userId)
            ->withProperties([
                'assessment_type' => $assessment->assessment_type,
                'total_amount' => $assessment->total_amount,
                'application_id' => $application->id,
            ])
            ->log('Assessment finalized');

        return $assessment;
    }
}
