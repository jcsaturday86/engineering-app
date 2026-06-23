<?php

namespace App\Services;

use App\DTOs\CollectionDTO;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Collection;
use App\Models\VoidTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CollectionService
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected AssessmentService $assessmentService,
    ) {}

    /**
     * Process a payment for an application.
     *
     * Validates no duplicate OR number, creates the collection with detail lines
     * from the assessment items, and transitions the application to paid status.
     *
     * @throws \InvalidArgumentException If OR number already exists.
     */
    public function processPayment(Application $application, CollectionDTO $dto): Collection
    {
        return DB::transaction(function () use ($application, $dto) {
            // Check for duplicate OR number
            $exists = Collection::where('or_number', $dto->or_number)->exists();
            if ($exists) {
                throw new \InvalidArgumentException(
                    "Official Receipt number '{$dto->or_number}' already exists."
                );
            }

            $changeAmount = max(0, $dto->amount_received - $dto->amount_due);

            // Find the billing for this application
            $billing = $application->billings()
                ->where('status', 'unpaid')
                ->latest()
                ->first();

            $collection = Collection::create([
                'application_id' => $application->id,
                'billing_id' => $billing?->id,
                'or_number' => $dto->or_number,
                'or_date' => $dto->or_date,
                'paid_by' => $dto->paid_by,
                'amount_due' => $dto->amount_due,
                'amount_received' => $dto->amount_received,
                'change_amount' => $changeAmount,
                'payment_mode' => $dto->payment_mode,
                'bank_name' => $dto->bank_name,
                'check_number' => $dto->check_number,
                'check_date' => $dto->check_date,
                'online_reference' => $dto->online_reference,
                'collected_by' => auth()->id(),
                'status' => 'active',
            ]);

            // Create collection detail lines from assessment items
            $summary = $this->assessmentService->getSummary($application);
            foreach ($summary['assessments'] as $type => $data) {
                foreach ($data['items'] as $item) {
                    $collection->collectionDetails()->create([
                        'fee_category' => $type,
                        'description' => $item->description,
                        'amount' => $item->amount,
                        'is_active' => true,
                    ]);
                }
            }

            // Update billing status
            if ($billing) {
                $billing->update(['status' => 'paid']);
            }

            // Transition application to paid
            $this->applicationService->transitionStatus($application, ApplicationStatus::PAID);

            return $collection->fresh(['collectionDetails']);
        });
    }

    /**
     * Void a collection with reason and password verification.
     *
     * @throws \InvalidArgumentException If password verification fails.
     */
    public function voidCollection(
        Collection $collection,
        string $reason,
        int $userId,
        string $password,
    ): VoidTransaction {
        return DB::transaction(function () use ($collection, $reason, $userId, $password) {
            // Verify user's password
            $user = \App\Models\User::findOrFail($userId);
            if (! Hash::check($password, $user->password)) {
                throw new \InvalidArgumentException('Invalid password. Void operation denied.');
            }

            // Void the collection
            $collection->update(['status' => 'void']);
            $collection->delete(); // Soft delete

            // Create void transaction record
            $voidTransaction = VoidTransaction::create([
                'collection_id' => $collection->id,
                'or_number' => $collection->or_number,
                'reason' => $reason,
                'voided_by' => $userId,
                'voided_at' => now(),
            ]);

            // Void the billing
            $billing = $collection->billing;
            if ($billing) {
                $billing->update(['status' => 'unpaid']);
            }

            // Roll back application status to billed
            $application = $collection->application;
            if ($application) {
                $application->update([
                    'status' => ApplicationStatus::BILLED->value,
                    'paid_at' => null,
                ]);
            }

            return $voidTransaction;
        });
    }

    /**
     * Get the data needed to print an official receipt.
     *
     * @return array{
     *     collection: Collection,
     *     details: \Illuminate\Database\Eloquent\Collection,
     *     application: Application,
     *     billing: ?Billing,
     * }
     */
    public function getOfficialReceiptData(Collection $collection): array
    {
        $collection->load(['collectionDetails', 'application', 'billing']);

        return [
            'collection' => $collection,
            'details' => $collection->collectionDetails->where('is_active', true),
            'application' => $collection->application,
            'billing' => $collection->billing,
        ];
    }
}
