<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            // Per-schedule mechanical inspection fee (mirrors BOPMS ann_inspection_f* tables).
            // insp_fee:              flat or per-unit inspection rate for this range
            // insp_excess_threshold: above this unit count, excess inspection rate kicks in
            // insp_excess_fee:       inspection rate per unit over the threshold
            $table->decimal('insp_fee', 15, 4)->default(0)->after('excess_every');
            $table->decimal('insp_excess_threshold', 15, 2)->default(0)->after('insp_fee');
            $table->decimal('insp_excess_fee', 15, 4)->default(0)->after('insp_excess_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropColumn(['insp_fee', 'insp_excess_threshold', 'insp_excess_fee']);
        });
    }
};
