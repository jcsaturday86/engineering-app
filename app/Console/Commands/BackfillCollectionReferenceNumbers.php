<?php

namespace App\Console\Commands;

use App\Models\Billing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCollectionReferenceNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billings:backfill-reference-no';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign collection_reference_no to existing billings that do not have one yet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $billings = Billing::withTrashed()
            ->whereNull('collection_reference_no')
            ->orderBy('created_at')
            ->get();

        if ($billings->isEmpty()) {
            $this->info('No billings need backfilling.');
            return self::SUCCESS;
        }

        $counters = [];

        DB::transaction(function () use ($billings, &$counters) {
            foreach ($billings as $billing) {
                $prefix = '40000' . $billing->created_at->format('ym');

                if (! isset($counters[$prefix])) {
                    $lastRef = Billing::withTrashed()
                        ->where('collection_reference_no', 'like', $prefix . '%')
                        ->orderByDesc('collection_reference_no')
                        ->value('collection_reference_no');
                    $counters[$prefix] = $lastRef ? ((int) substr($lastRef, -4)) : 0;
                }

                $counters[$prefix]++;
                $billing->collection_reference_no = sprintf('%s%04d', $prefix, $counters[$prefix]);
                $billing->save();
            }
        });

        $this->info("Backfilled {$billings->count()} billing record(s) with a collection reference number.");

        return self::SUCCESS;
    }
}
