<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_use_and_zoning_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('occupancy_sub_group_id');
            $table->decimal('range_from', 15, 2)->default(0);
            $table->decimal('range_to', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('excess_of', 15, 2)->default(0);
            $table->decimal('percentage', 10, 6)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('occupancy_sub_group_id')->references('id')->on('occupancy_sub_groups');
            $table->index(['occupancy_sub_group_id', 'range_from', 'range_to'], 'luzf_subgroup_range_idx');
        });

        Schema::create('certification_zoning_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('occupancy_sub_group_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('occupancy_sub_group_id')->references('id')->on('occupancy_sub_groups');
        });

        // Migrate data from fee_schedules
        $lcFeeTypeId = DB::table('fee_types')
            ->join('fee_categories', 'fee_types.fee_category_id', '=', 'fee_categories.id')
            ->where('fee_categories.code', 'ZONING_LC')
            ->where('fee_types.code', 'ZONING_LC_FEE')
            ->value('fee_types.id');

        if ($lcFeeTypeId) {
            $rows = DB::table('fee_schedules')->where('fee_type_id', $lcFeeTypeId)->get();
            $now = now();
            foreach ($rows as $row) {
                DB::table('land_use_and_zoning_fees')->insert([
                    'occupancy_sub_group_id' => $row->occupancy_sub_group_id ?? 1,
                    'range_from' => $row->range_from,
                    'range_to' => $row->range_to,
                    'amount' => $row->fixed_fee,
                    'excess_of' => $row->excess_threshold,
                    'percentage' => $row->percentage,
                    'is_active' => $row->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            DB::table('fee_schedules')->where('fee_type_id', $lcFeeTypeId)->delete();
        }

        $certFeeTypeId = DB::table('fee_types')
            ->join('fee_categories', 'fee_types.fee_category_id', '=', 'fee_categories.id')
            ->where('fee_categories.code', 'ZONING_CERT')
            ->where('fee_types.code', 'ZONING_CERT_FEE')
            ->value('fee_types.id');

        if ($certFeeTypeId) {
            $rows = DB::table('fee_schedules')->where('fee_type_id', $certFeeTypeId)->get();
            $now = now();
            foreach ($rows as $row) {
                DB::table('certification_zoning_fees')->insert([
                    'occupancy_sub_group_id' => $row->occupancy_sub_group_id,
                    'amount' => $row->fixed_fee,
                    'is_active' => $row->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            DB::table('fee_schedules')->where('fee_type_id', $certFeeTypeId)->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_zoning_fees');
        Schema::dropIfExists('land_use_and_zoning_fees');
    }
};
