<?php

namespace App\Services;

use App\DTOs\OccupancyApplicationDTO;
use App\Enums\ApplicationStatus;
use App\Models\OccupancyApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OccupancyApplicationService
{
    public function generateApplicationNumber(): string
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');

        $counter = DB::table('occupancy_applications')
            ->where('app_year', $year)
            ->where('app_month', $month)
            ->lockForUpdate()
            ->max('app_counter');

        $nextCounter = ($counter ?? 0) + 1;

        return sprintf('%04d-%02d-%05d', $year, $month, $nextCounter);
    }

    public function create(OccupancyApplicationDTO $dto): OccupancyApplication
    {
        return DB::transaction(function () use ($dto) {
            $year = (int) now()->format('Y');
            $month = (int) now()->format('m');

            $counter = DB::table('occupancy_applications')
                ->where('app_year', $year)
                ->where('app_month', $month)
                ->lockForUpdate()
                ->max('app_counter');

            $nextCounter = ($counter ?? 0) + 1;
            $applicationNumber = sprintf('OP-%04d-%02d-%05d', $year, $month, $nextCounter);

            $data = array_merge($dto->toArray(), [
                'app_year' => $year,
                'app_month' => $month,
                'app_counter' => $nextCounter,
                'application_number' => $applicationNumber,
                'status' => ApplicationStatus::DRAFT->value,
            ]);

            return OccupancyApplication::create($data);
        });
    }

    public function update(OccupancyApplication $application, OccupancyApplicationDTO $dto): OccupancyApplication
    {
        $application->update($dto->toArray());

        return $application->fresh();
    }

    public function submit(OccupancyApplication $application): OccupancyApplication
    {
        return $this->transitionStatus($application, ApplicationStatus::SUBMITTED);
    }

    public function cancel(OccupancyApplication $application, string $reason): OccupancyApplication
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

    public function getListForAssessment(): Collection
    {
        return OccupancyApplication::whereIn('status', [
            ApplicationStatus::SUBMITTED->value,
        ])->orderBy('submitted_at')->get();
    }

    public function getListForPayment(): Collection
    {
        return OccupancyApplication::where('status', ApplicationStatus::BILLED->value)
            ->orderBy('updated_at')
            ->get();
    }

    public function transitionStatus(OccupancyApplication $application, ApplicationStatus $newStatus): OccupancyApplication
    {
        $currentStatus = ApplicationStatus::from($application->status);

        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$currentStatus->label()}' to '{$newStatus->label()}'."
            );
        }

        $updateData = ['status' => $newStatus->value];

        $updateData = match ($newStatus) {
            ApplicationStatus::SUBMITTED => array_merge($updateData, ['submitted_at' => now()]),
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
