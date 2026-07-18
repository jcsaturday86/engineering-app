<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use App\Models\FeeSchedule;
use App\Models\FeeType;
use App\Models\PermitType;
use Illuminate\Http\Request;

class FeeScheduleController extends Controller
{
    /**
     * Show all fee categories with types (settings/fees page).
     */
    public function index()
    {
        // DP fee rates are managed exclusively via the dedicated Demolition Fees settings page.
        $permitTypes = PermitType::where('is_active', true)
            ->where('code', '!=', 'DP')
            ->with(['feeCategories' => function ($q) {
                $q->where('is_active', true)
                    ->where('code', '!=', 'MECH_INSP')
                    ->where('code', '!=', 'DEMO_FEE')
                    ->whereNotIn('code', ['AI_AC', 'AI_MACH', 'AI_ESC', 'AI_ELEV', 'AI_GENSET'])
                    ->orderBy('sort_order')
                    ->withCount('feeTypes');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('settings.fees', compact('permitTypes'));
    }

    public function showCategory(FeeCategory $feeCategory)
    {
        $feeCategory->load(['permitType', 'feeTypes' => function ($q) {
            $q->orderBy('sort_order')->withCount('feeSchedules');
        }]);

        return view('settings.fee-category', compact('feeCategory'));
    }

    /**
     * Show fee schedules for a specific fee type with inline edit.
     */
    public function showType(FeeType $feeType)
    {
        $feeType->load(['feeCategory.permitType', 'feeSchedules' => function ($q) {
            $q->orderBy('range_from');
        }]);

        return view('settings.fee-type', compact('feeType'));
    }

    /**
     * Create a new fee type under a category.
     */
    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'computation_method' => 'nullable|string|max:50',
            'has_excess' => 'boolean',
            'has_minimum' => 'boolean',
            'has_maximum' => 'boolean',
        ]);

        $validated['has_excess'] = $request->boolean('has_excess');
        $validated['has_minimum'] = $request->boolean('has_minimum');
        $validated['has_maximum'] = $request->boolean('has_maximum');
        $validated['is_active'] = true;

        $maxSort = FeeType::where('fee_category_id', $validated['fee_category_id'])->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSort + 1;

        $feeType = FeeType::create($validated);

        return redirect()->route('settings.fees.type', $feeType)
            ->with('success', "Fee type \"{$feeType->name}\" created successfully.");
    }

    /**
     * Update fee type details.
     */
    public function updateType(Request $request, FeeType $feeType)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'computation_method' => 'nullable|string|max:50',
            'has_excess' => 'boolean',
            'has_minimum' => 'boolean',
            'has_maximum' => 'boolean',
        ]);

        $validated['has_excess'] = $request->boolean('has_excess');
        $validated['has_minimum'] = $request->boolean('has_minimum');
        $validated['has_maximum'] = $request->boolean('has_maximum');

        $feeType->update($validated);

        return back()->with('success', "Fee type \"{$feeType->name}\" updated.");
    }

    /**
     * Add a fee schedule range row.
     */
    public function storeSchedule(Request $request, FeeType $feeType)
    {
        $validated = $request->validate([
            'range_from' => 'nullable|numeric|min:0',
            'range_to' => 'nullable|numeric|min:0',
            'fixed_fee' => 'nullable|numeric|min:0',
            'fee_per_unit' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'excess_threshold' => 'nullable|numeric|min:0',
            'excess_fee' => 'nullable|numeric|min:0',
            'excess_every' => 'nullable|numeric|min:0',
            'minimum_fee' => 'nullable|numeric|min:0',
            'maximum_fee' => 'nullable|numeric|min:0',
        ]);

        $validated['fee_type_id'] = $feeType->id;
        $validated['is_active'] = true;

        FeeSchedule::create($validated);

        return back()->with('success', 'Fee schedule row added.');
    }

    /**
     * Update a fee schedule row.
     */
    public function updateSchedule(Request $request, FeeSchedule $feeSchedule)
    {
        $validated = $request->validate([
            'range_from' => 'nullable|numeric|min:0',
            'range_to' => 'nullable|numeric|min:0',
            'fixed_fee' => 'nullable|numeric|min:0',
            'fee_per_unit' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'excess_threshold' => 'nullable|numeric|min:0',
            'excess_fee' => 'nullable|numeric|min:0',
            'excess_every' => 'nullable|numeric|min:0',
            'minimum_fee' => 'nullable|numeric|min:0',
            'maximum_fee' => 'nullable|numeric|min:0',
        ]);

        $feeSchedule->update($validated);

        return back()->with('success', 'Fee schedule row updated.');
    }

    /**
     * Delete a fee schedule row.
     */
    public function destroySchedule(FeeSchedule $feeSchedule)
    {
        $feeTypeId = $feeSchedule->fee_type_id;
        $feeSchedule->delete();

        return back()->with('success', 'Fee schedule row deleted.');
    }
}
