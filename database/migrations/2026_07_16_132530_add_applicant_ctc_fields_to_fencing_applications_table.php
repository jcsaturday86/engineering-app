<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fencing_applications', function (Blueprint $table) {
            $table->string('applicant_ctc_no')->nullable()->after('applicant_zip_code');
            $table->date('applicant_ctc_date_issued')->nullable()->after('applicant_ctc_no');
            $table->string('applicant_ctc_issued_at')->nullable()->after('applicant_ctc_date_issued');
        });
    }

    public function down(): void
    {
        Schema::table('fencing_applications', function (Blueprint $table) {
            $table->dropColumn(['applicant_ctc_no', 'applicant_ctc_date_issued', 'applicant_ctc_issued_at']);
        });
    }
};
