<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('billing_number', 30)->unique();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('unpaid'); // unpaid, partial, paid, void
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('application_id');
        });

        Schema::create('billing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained();
            $table->foreignId('billing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('or_number', 30);
            $table->date('or_date');
            $table->string('paid_by');
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->decimal('amount_received', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('payment_mode', 20)->default('cash'); // cash, check, online
            $table->string('bank_name')->nullable();
            $table->string('check_number')->nullable();
            $table->date('check_date')->nullable();
            $table->string('online_reference')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active'); // active, void
            $table->softDeletes();
            $table->timestamps();

            $table->unique('or_number');
            $table->index('application_id');
        });

        Schema::create('collection_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('fee_category');
            $table->string('description');
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('void_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained();
            $table->string('or_number');
            $table->text('reason');
            $table->foreignId('voided_by')->constrained('users');
            $table->timestamp('voided_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void_transactions');
        Schema::dropIfExists('collection_details');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('billing_items');
        Schema::dropIfExists('billings');
    }
};
