<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'assessments',
            'billings',
            'collections',
            'permits',
            'documents',
            'application_requirements',
            'application_occupancy_groups',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('applicationable_type', 10)->nullable()->after('id');
                $table->unsignedBigInteger('applicationable_id')->nullable()->after('applicationable_type');
                $table->index(['applicationable_type', 'applicationable_id'], $table->getTable() . '_applicationable_idx');
            });

            // Backfill: all existing rows are BP applications
            DB::table($tableName)
                ->whereNotNull('application_id')
                ->update([
                    'applicationable_type' => 'bp',
                    'applicationable_id' => DB::raw('application_id'),
                ]);
        }
    }

    public function down(): void
    {
        $tables = [
            'assessments',
            'billings',
            'collections',
            'permits',
            'documents',
            'application_requirements',
            'application_occupancy_groups',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName . '_applicationable_idx');
                $table->dropColumn(['applicationable_type', 'applicationable_id']);
            });
        }
    }
};
