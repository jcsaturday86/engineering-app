<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupancy_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('occupancy_applications', 'fsic_no')) {
                $table->string('fsic_no')->nullable()->after('fsec_issued_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_applications', function (Blueprint $table) {
            $table->dropColumn(['fsic_no']);
        });
    }
};
