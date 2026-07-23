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
        Schema::table('annual_inspection_applications', function (Blueprint $table) {
            $table->string('occupancy_no')->nullable()->after('issued_date');
            $table->date('occupancy_issued_date')->nullable()->after('occupancy_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_inspection_applications', function (Blueprint $table) {
            $table->dropColumn(['occupancy_no', 'occupancy_issued_date']);
        });
    }
};
