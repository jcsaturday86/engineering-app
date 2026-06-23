<?php

namespace App\Actions;

use App\DTOs\CollectionDTO;
use App\Models\Application;
use App\Models\Collection;
use App\Services\CollectionService;

class ProcessPaymentAction
{
    public function __construct(
        protected CollectionService $collectionService,
    ) {}

    /**
     * Process payment for an application and log the activity.
     *
     * The CollectionService handles creating the collection, detail lines,
     * and transitioning the application status to paid.
     */
    public function __invoke(Application $application, CollectionDTO $dto, int $userId): Collection
    {
        $collection = $this->collectionService->processPayment($application, $dto);

        activity()
            ->performedOn($collection)
            ->causedBy($userId)
            ->withProperties([
                'or_number' => $collection->or_number,
                'amount_received' => $collection->amount_received,
                'payment_mode' => $collection->payment_mode,
                'application_id' => $application->id,
            ])
            ->log('Payment processed');

        return $collection;
    }
}
