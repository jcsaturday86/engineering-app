<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_type_id')->constrained()->cascadeOnDelete();

            // Second level: an item nested under a heading/group row.
            $table->foreignId('parent_id')->nullable()->constrained('document_requirements')->cascadeOnDelete();

            // text, not string — several official entries exceed 255 chars.
            $table->text('name');

            // The "(In case the applicant is not the registered owner...)" parenthetical,
            // kept out of the name so it can be styled as helper text.
            $table->text('condition_note')->nullable();

            $table->enum('requirement_level', ['mandatory', 'conditional', 'optional'])->default('mandatory');

            // false = pure heading row that accepts no upload of its own.
            $table->boolean('is_uploadable')->default(true);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['permit_type_id', 'parent_id', 'sort_order'], 'doc_reqs_type_parent_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
