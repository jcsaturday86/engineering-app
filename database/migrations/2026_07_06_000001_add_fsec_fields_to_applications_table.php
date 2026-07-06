<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 2026_06_24_100000 migration intended to add these via
     * ->after('bp_issued_date'), but bp_issued_date was never actually
     * added to this table, so that ALTER silently never applied here.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'fsec_no')) {
                $table->string('fsec_no')->nullable();
            }
            if (! Schema::hasColumn('applications', 'fsec_issued_date')) {
                $table->date('fsec_issued_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['fsec_no', 'fsec_issued_date']);
        });
    }
};
