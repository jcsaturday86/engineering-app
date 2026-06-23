<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Permit;
use Illuminate\Support\Facades\DB;

class PermitService
{
    /**
     * Generate a permit for a paid application.
     */
    public function generatePermit(Application $application): Permit
    {
        return DB::transaction(function () use ($application) {
            $permitTypeId = $application->permit_type_id;
            $permitNumber = $this->getPermitNumber($permitTypeId);

            $year = (int) now()->format('Y');
            $month = (int) now()->format('m');

            // Extract the counter from the generated permit number
            $counter = (int) substr($permitNumber, -5);

            return Permit::create([
                'application_id' => $application->id,
                'permit_type_id' => $permitTypeId,
                'permit_year' => $year,
                'permit_month' => $month,
                'permit_counter' => $counter,
                'permit_number' => $permitNumber,
                'issued_date' => now()->toDateString(),
                'processed_by' => auth()->id(),
                'status' => 'generated',
            ]);
        });
    }

    /**
     * Generate a permit number in YYYY-MM-NNNNN format.
     */
    public function getPermitNumber(int $permitTypeId): string
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');

        $counter = DB::table('permits')
            ->where('permit_type_id', $permitTypeId)
            ->where('permit_year', $year)
            ->where('permit_month', $month)
            ->lockForUpdate()
            ->max('permit_counter');

        $nextCounter = ($counter ?? 0) + 1;

        return sprintf('%04d-%02d-%05d', $year, $month, $nextCounter);
    }
}
