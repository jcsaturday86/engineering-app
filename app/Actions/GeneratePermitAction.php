<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Permit;
use App\Services\ApplicationService;
use App\Services\PermitService;

class GeneratePermitAction
{
    public function __construct(
        protected PermitService $permitService,
        protected ApplicationService $applicationService,
    ) {}

    /**
     * Generate a permit for the application and transition status.
     */
    public function __invoke(Application $application, int $userId): Permit
    {
        $permit = $this->permitService->generatePermit($application);

        // Transition application to permit_generated
        $this->applicationService->transitionStatus($application, ApplicationStatus::PERMIT_GENERATED);

        activity()
            ->performedOn($permit)
            ->causedBy($userId)
            ->withProperties([
                'permit_number' => $permit->permit_number,
                'application_id' => $application->id,
                'permit_type_id' => $permit->permit_type_id,
            ])
            ->log('Permit generated');

        return $permit;
    }
}
