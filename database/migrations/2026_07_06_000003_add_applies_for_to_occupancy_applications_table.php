<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupancy_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('occupancy_applications', 'applies_for')) {
                $table->string('applies_for', 50)->nullable()->after('fsic_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_applications', function (Blueprint $table) {
            $table->dropColumn(['applies_for']);
        });
    }
};
