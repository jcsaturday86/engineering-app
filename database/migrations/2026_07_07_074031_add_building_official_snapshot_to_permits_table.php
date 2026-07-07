<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->string('building_official_name')->nullable()->after('revoke_reason');
            $table->string('building_official_title')->nullable()->after('building_official_name');
            $table->string('building_official_designation')->nullable()->after('building_official_title');
            $table->string('building_official_license_no')->nullable()->after('building_official_designation');
        });

        // Best-effort backfill for permits generated before this snapshot existed —
        // there's no historical record of who held the role at each past generation time.
        $buildingOfficial = DB::table('signatories')->where('role', 'building_official')->where('is_active', true)->first();

        if ($buildingOfficial) {
            DB::table('permits')->where('status', '!=', 'revoked')->update([
                'building_official_name' => $buildingOfficial->name,
                'building_official_title' => $buildingOfficial->title,
                'building_official_designation' => $buildingOfficial->designation,
                'building_official_license_no' => $buildingOfficial->license_no,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropColumn([
                'building_official_name',
                'building_official_title',
                'building_official_designation',
                'building_official_license_no',
            ]);
        });
    }
};
