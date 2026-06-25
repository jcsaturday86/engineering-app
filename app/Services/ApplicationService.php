<?php

namespace App\Services;

use App\DTOs\ApplicationDTO;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\PermitType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    /**
     * Generate an application number in YYYY-MM-NNNNN format.
     *
     * Atomically increments the counter for the given permit type, year, and month.
     */
    public function generateApplicationNumber(int $permitTypeId): string
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');

        $counter = DB::table('applications')
            ->where('permit_type_id', $permitTypeId)
            ->where('app_year', $year)
            ->where('app_month', $month)
            ->lockForUpdate()
            ->max('app_counter');

        $nextCounter = ($counter ?? 0) + 1;

        return sprintf('%04d-%02d-%05d', $year, $month, $nextCounter);
    }

    /**
     * Create a new application with generated number and draft status.
     */
    public function create(ApplicationDTO $dto, int $permitTypeId): Application
    {
        return DB::transaction(function () use ($dto, $permitTypeId) {
            $year = (int) now()->format('Y');
            $month = (int) now()->format('m');

            $counter = DB::table('applications')
                ->where('permit_type_id', $permitTypeId)
                ->where('app_year', $year)
                ->where('app_month', $month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $applicationNumber = sprintf('%04d-%02d-%05d', $year, $month, $nextCounter);

            $data = array_merge($dto->toArray(), [
                'permit_type_id' => $permitTypeId,
                'app_year' => $year,
                'app_month' => $month,
                'app_counter' => $nextCounter,
                'application_number' => $applicationNumber,
                'status' => ApplicationStatus::DRAFT->value,
            ]);

            return Application::create($data);
        });
    }

    /**
     * Update an existing application's data.
     */
    public function update(Application $application, ApplicationDTO $dto): Application
    {
        $application->update($dto->toArray());

        return $application->fresh();
    }

    /**
     * Submit an application for processing.
     */
    public function submit(Application $application): Application
    {
        return $this->transitionStatus($application, ApplicationStatus::SUBMITTED);
    }

    /**
     * Cancel an application with a reason.
     */
    public function cancel(Application $application, string $reason): Application
    {
        $currentStatus = ApplicationStatus::from($application->status);

        if (! $currentStatus->canTransitionTo(ApplicationStatus::CANCELLED)) {
            throw new \InvalidArgumentException(
                "Cannot cancel application in '{$currentStatus->label()}' status."
            );
        }

        $application->update([
            'status' => ApplicationStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $application->fresh();
    }

    /**
     * Get applications that are ready for assessment by permit type.
     */
    public function getListForAssessment(string $permitTypeCode): Collection
    {
        $permitType = PermitType::where('code', $permitTypeCode)->firstOrFail();

        return Application::where('permit_type_id', $permitType->id)
            ->whereIn('status', [
                ApplicationStatus::SUBMITTED->value,
                ApplicationStatus::ZONING_ASSESSED->value,
            ])
            ->orderBy('submitted_at')
            ->get();
    }

    /**
     * Get applications that are billed and ready for payment.
     */
    public function getListForPayment(): Collection
    {
        return Application::where('status', ApplicationStatus::BILLED->value)
            ->orderBy('updated_at')
            ->get();
    }

    /**
     * Transition an application to a new status with validation.
     *
     * @throws \InvalidArgumentException If the transition is not allowed.
     */
    public function transitionStatus(Application $application, ApplicationStatus $newStatus): Application
    {
        $currentStatus = ApplicationStatus::from($application->status);

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$currentStatus->label()}' to '{$newStatus->label()}'."
            );
        }

        $updateData = ['status' => $newStatus->value];

        // Set corresponding timestamp based on new status
        $updateData = match ($newStatus) {
            ApplicationStatus::SUBMITTED => array_merge($updateData, ['submitted_at' => now()]),
            ApplicationStatus::FOR_ZONING_ASSESSMENT => array_merge($updateData, ['submitted_at' => now()]),
            ApplicationStatus::ENGINEERING_ASSESSED => array_merge($updateData, ['assessed_at' => now()]),
            ApplicationStatus::PAID => array_merge($updateData, ['paid_at' => now()]),
            ApplicationStatus::RELEASED => array_merge($updateData, ['released_at' => now()]),
            ApplicationStatus::CANCELLED => array_merge($updateData, ['cancelled_at' => now()]),
            default => $updateData,
        };

        $application->update($updateData);

        return $application->fresh();
    }
}
