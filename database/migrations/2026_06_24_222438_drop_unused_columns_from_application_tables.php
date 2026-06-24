<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'bp_number',
                'bp_issued_date',
                'fsec_no',
                'fsec_issued_date',
                'applies_for',
                'completion_date',
            ]);
        });

        Schema::table('occupancy_applications', function (Blueprint $table) {
            $table->dropColumn('applies_for');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('bp_number', 30)->nullable()->after('owner_date_signed');
            $table->date('bp_issued_date')->nullable()->after('bp_number');
            $table->string('fsec_no', 50)->nullable()->after('bp_issued_date');
            $table->date('fsec_issued_date')->nullable()->after('fsec_no');
            $table->string('applies_for', 50)->nullable()->after('fsec_issued_date');
            $table->date('completion_date')->nullable()->after('applies_for');
        });

        Schema::table('occupancy_applications', function (Blueprint $table) {
            $table->string('applies_for', 50)->nullable()->after('completion_date');
        });
    }
};
