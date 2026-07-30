<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequirement;
use App\Models\PermitType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Settings → Document Requirements.
 *
 * Staff maintain, per permit type, the list of documents an online client must
 * attach. Every permit type is managed identically — a type with no rows simply
 * requires no documents, which is how Annual Inspection currently behaves
 * without being special-cased anywhere.
 */
class DocumentRequirementController extends Controller
{
    /**
     * Drill-down index: one card per permit type with its requirement count.
     */
    public function index()
    {
        $permitTypes = PermitType::where('is_active', true)
            ->withCount([
                'documentRequirements',
                'documentRequirements as mandatory_requirements_count' => fn ($q) => $q
                    ->where('is_active', true)
                    ->where('is_uploadable', true)
                    ->where('requirement_level', 'mandatory'),
            ])
            ->orderBy('sort_order')
            ->get();

        return view('settings.document-requirements', compact('permitTypes'));
    }

    /**
     * Editor for a single permit type's requirement list.
     */
    public function showType(PermitType $permitType)
    {
        $requirements = $permitType->documentRequirements()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        // Candidate parents for the "nest under" picker — top-level rows only,
        // since the structure is deliberately capped at two levels.
        $parentOptions = $permitType->documentRequirements()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('settings.document-requirements-type', compact('permitType', 'requirements', 'parentOptions'));
    }

    public function store(Request $request, PermitType $permitType)
    {
        $data = $this->validated($request, $permitType);

        $data['permit_type_id'] = $permitType->id;
        $data['sort_order'] = $permitType->documentRequirements()
            ->where('parent_id', $data['parent_id'])
            ->max('sort_order') + 1;

        DocumentRequirement::create($data);

        return back()->with('success', 'Requirement added.');
    }

    public function update(Request $request, DocumentRequirement $documentRequirement)
    {
        $data = $this->validated($request, $documentRequirement->permitType, $documentRequirement);

        $documentRequirement->update($data);

        return back()->with('success', 'Requirement updated.');
    }

    public function destroy(DocumentRequirement $documentRequirement)
    {
        $childCount = $documentRequirement->children()->count();

        $documentRequirement->delete();

        return back()->with('success', $childCount > 0
            ? "Requirement deleted, along with {$childCount} nested item(s)."
            : 'Requirement deleted.');
    }

    /**
     * Shared validation for store/update.
     */
    private function validated(Request $request, PermitType $permitType, ?DocumentRequirement $existing = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:1000',
            'condition_note' => 'nullable|string|max:1000',
            'requirement_level' => ['required', Rule::in(['mandatory', 'conditional', 'optional'])],
            'is_uploadable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'parent_id' => [
                'nullable',
                Rule::exists('document_requirements', 'id')->where('permit_type_id', $permitType->id),
            ],
        ]);

        $data['is_uploadable'] = $request->boolean('is_uploadable');
        $data['is_active'] = $request->boolean('is_active');
        $data['parent_id'] = $data['parent_id'] ?? null;

        // Structure is capped at two levels, and a row can never parent itself.
        if ($existing && $data['parent_id']) {
            if ((int) $data['parent_id'] === $existing->id) {
                $data['parent_id'] = null;
            } elseif (DocumentRequirement::whereKey($data['parent_id'])->whereNotNull('parent_id')->exists()) {
                $data['parent_id'] = null;
            }
        }

        return $data;
    }
}
