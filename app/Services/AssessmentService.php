<?php

namespace App\Services;

use App\DTOs\AssessmentItemDTO;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    /**
     * Create a new assessment for an application.
     */
    public function createAssessment(Application $application, string $type): Assessment
    {
        return Assessment::create([
            'application_id' => $application->id,
            'assessment_type' => $type,
            'status' => 'draft',
        ]);
    }

    /**
     * Add a line item to an assessment.
     */
    public function addItem(Assessment $assessment, AssessmentItemDTO $dto): AssessmentItem
    {
        return $assessment->assessmentItems()->create($dto->toArray());
    }

    /**
     * Soft-delete an assessment item (marks as inactive).
     */
    public function removeItem(AssessmentItem $item): void
    {
        $item->update(['is_active' => false]);
        $item->delete();
    }

    /**
     * Calculate the total of all active items in an assessment.
     */
    public function calculateTotal(Assessment $assessment): float
    {
        return (float) $assessment->assessmentItems()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    /**
     * Finalize an assessment: lock it, set the assessor, and update the total.
     */
    public function finalize(Assessment $assessment, int $userId): Assessment
    {
        return DB::transaction(function () use ($assessment, $userId) {
            $total = $this->calculateTotal($assessment);

            $assessment->update([
                'total_amount' => $total,
                'status' => 'finalized',
                'assessed_by' => $userId,
                'finalized_at' => now(),
            ]);

            return $assessment->fresh();
        });
    }

    /**
     * Get a summary of all assessments for an application, grouped by type.
     *
     * @return array{
     *     assessments: array<string, array{
     *         assessment: Assessment,
     *         items: \Illuminate\Database\Eloquent\Collection,
     *         total: float,
     *     }>,
     *     grand_total: float,
     * }
     */
    public function getSummary(Application $application): array
    {
        $assessments = $application->assessments()
            ->with(['assessmentItems' => fn ($q) => $q->where('is_active', true)->whereNull('deleted_at')])
            ->get();

        $grouped = [];
        $grandTotal = 0;

        foreach ($assessments as $assessment) {
            $total = (float) $assessment->assessmentItems->sum('amount');
            $grandTotal += $total;

            $grouped[$assessment->assessment_type] = [
                'assessment' => $assessment,
                'items' => $assessment->assessmentItems,
                'total' => $total,
            ];
        }

        return [
            'assessments' => $grouped,
            'grand_total' => round($grandTotal, 2),
        ];
    }
}
