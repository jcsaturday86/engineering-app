<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The six permit application tables that gain an Engineering
     * approve/disapprove review step for online submissions.
     */
    private const TABLES = [
        'applications',
        'occupancy_applications',
        'fencing_applications',
        'demolition_applications',
        'signage_applications',
        'annual_inspection_applications',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'review_remarks')) {
                    $blueprint->text('review_remarks')->nullable()->after('approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'review_remarks')) {
                    $blueprint->dropColumn('review_remarks');
                }
            });
        }
    }
};
