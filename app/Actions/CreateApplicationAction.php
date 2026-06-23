<?php

namespace App\Actions;

use App\DTOs\ApplicationDTO;
use App\Models\Application;
use App\Services\ApplicationService;

class CreateApplicationAction
{
    public function __construct(
        protected ApplicationService $applicationService,
    ) {}

    /**
     * Create a new application and log the activity.
     */
    public function __invoke(ApplicationDTO $dto, int $permitTypeId, int $userId): Application
    {
        $application = $this->applicationService->create($dto, $permitTypeId);

        // Update the entered_by field
        $application->update(['entered_by' => $userId]);

        activity()
            ->performedOn($application)
            ->causedBy($userId)
            ->withProperties([
                'application_number' => $application->application_number,
                'permit_type_id' => $permitTypeId,
            ])
            ->log('Application created');

        return $application;
    }
}
