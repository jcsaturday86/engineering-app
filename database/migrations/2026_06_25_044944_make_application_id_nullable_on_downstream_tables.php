<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'assessments',
        'billings',
        'collections',
        'permits',
        'documents',
        'application_requirements',
        'application_occupancy_groups',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND COLUMN_NAME = 'application_id' AND REFERENCED_TABLE_NAME IS NOT NULL");

            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY `application_id` BIGINT UNSIGNED NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `application_id` BIGINT UNSIGNED NOT NULL DEFAULT 0");
        }
    }
};
