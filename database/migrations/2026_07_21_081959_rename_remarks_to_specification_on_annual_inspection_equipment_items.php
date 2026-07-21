<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Native RENAME COLUMN (MySQL 8+/MariaDB 10.5+) avoids requiring doctrine/dbal,
        // which this project does not have installed.
        DB::statement('ALTER TABLE annual_inspection_equipment_items RENAME COLUMN remarks TO specification');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE annual_inspection_equipment_items RENAME COLUMN specification TO remarks');
    }
};
