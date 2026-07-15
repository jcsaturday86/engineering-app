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
        Schema::table('fee_types', function (Blueprint $table) {
            // Physical unit the fee is measured in (e.g. "sq.m.", "lineal meter(s)", "fixture(s)") — drives
            // the quantity-input label in the assessment UI instead of hard-coded per-view JS maps.
            $table->string('unit_label', 40)->nullable()->after('computation_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn('unit_label');
        });
    }
};
