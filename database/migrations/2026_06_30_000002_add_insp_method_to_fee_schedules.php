<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            // flat     = insp_fee is a flat amount for this range (A. refrig/AC types)
            // per_unit = insp_fee × unit, with optional grouped excess ÷ every (D/H/L engines, most O)
            // tiered   = min(unit,threshold)×fee + max(0,unit−threshold)×excess_fee (C elevators)
            $table->enum('insp_method', ['flat', 'per_unit', 'tiered'])->default('per_unit')->after('insp_fee');
            // For grouped excess: ₱insp_excess_fee per every N units above threshold (B/D/H/L/O pattern)
            $table->decimal('insp_excess_every', 10, 2)->default(1)->after('insp_excess_fee');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropColumn(['insp_method', 'insp_excess_every']);
        });
    }
};
