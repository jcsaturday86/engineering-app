<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fencing_applications', function (Blueprint $table) {
            $table->string('inspector_name')->nullable()->after('design_professional_tin');
            $table->string('inspector_address')->nullable()->after('inspector_name');
            $table->string('inspector_prc_no')->nullable()->after('inspector_address');
            $table->date('inspector_prc_validity')->nullable()->after('inspector_prc_no');
            $table->string('inspector_ptr_no')->nullable()->after('inspector_prc_validity');
            $table->date('inspector_ptr_date_issued')->nullable()->after('inspector_ptr_no');
            $table->string('inspector_ptr_issued_at')->nullable()->after('inspector_ptr_date_issued');
            $table->string('inspector_tin')->nullable()->after('inspector_ptr_issued_at');
        });

        Schema::dropIfExists('fencing_inspectors');
    }

    public function down(): void
    {
        Schema::create('fencing_inspectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fencing_application_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('prc_no')->nullable();
            $table->date('prc_validity')->nullable();
            $table->string('ptr_no')->nullable();
            $table->date('ptr_date_issued')->nullable();
            $table->string('ptr_issued_at')->nullable();
            $table->string('tin')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('fencing_applications', function (Blueprint $table) {
            $table->dropColumn([
                'inspector_name',
                'inspector_address',
                'inspector_prc_no',
                'inspector_prc_validity',
                'inspector_ptr_no',
                'inspector_ptr_date_issued',
                'inspector_ptr_issued_at',
                'inspector_tin',
            ]);
        });
    }
};
