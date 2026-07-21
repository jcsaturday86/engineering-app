<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // group_code values used going forward (GE/ELN/MACH/ACREF/ELEV/ESC) are all
        // well within the existing string(10) column - no width change needed.
        Schema::table('annual_inspection_permit_units', function (Blueprint $table) {
            $table->foreignId('assessment_item_id')->nullable()->after('annual_inspection_application_id')
                ->constrained('assessment_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_inspection_permit_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_item_id');
        });
    }
};
