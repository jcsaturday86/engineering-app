<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained();
            $table->foreignId('permit_type_id')->constrained();
            $table->year('permit_year');
            $table->unsignedTinyInteger('permit_month');
            $table->unsignedInteger('permit_counter');
            $table->string('permit_number', 30)->unique();
            $table->date('issued_date')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('generated'); // generated, signed, released
            $table->softDeletes();
            $table->timestamps();

            $table->index(['permit_type_id', 'permit_year']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->unsignedInteger('counter')->nullable();
            $table->date('document_date')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('permits');
    }
};
