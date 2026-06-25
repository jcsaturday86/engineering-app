<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupancy_applications', function (Blueprint $table) {
            $table->string('project_title', 255)->nullable()->after('applicant_zip_code');
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_applications', function (Blueprint $table) {
            $table->dropColumn('project_title');
        });
    }
};
