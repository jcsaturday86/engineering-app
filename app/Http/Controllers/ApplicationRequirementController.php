<?php

namespace App\Http\Controllers;

use App\Models\ApplicationRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves uploaded requirement documents to whoever is entitled to see them.
 *
 * Files live on the `public` disk under requirements/{type}-{id}/, which is
 * web-reachable through the storage symlink — but the filenames are random, so
 * nothing links to them directly. Streaming through here instead means every
 * view is access-checked: staff who can see applications, or the client who
 * owns this one. Property titles and tax declarations should not be readable
 * by any signed-in client who guesses a URL.
 */
class ApplicationRequirementController extends Controller
{
    /**
     * Open the document inline (renders in a browser tab for PDF/JPG/PNG).
     */
    public function view(ApplicationRequirement $applicationRequirement): StreamedResponse
    {
        $this->authorizeAccess($applicationRequirement);

        return Storage::disk('public')->response(
            $applicationRequirement->file_path,
            $applicationRequirement->original_filename,
            ['Content-Disposition' => 'inline; filename="' . addslashes($applicationRequirement->original_filename) . '"']
        );
    }

    /**
     * Download the document under its original filename.
     */
    public function download(ApplicationRequirement $applicationRequirement): StreamedResponse
    {
        $this->authorizeAccess($applicationRequirement);

        return Storage::disk('public')->download(
            $applicationRequirement->file_path,
            $applicationRequirement->original_filename
        );
    }

    /**
     * Staff who can view applications, or the client who owns this one.
     *
     * Also 404s when the row survives but the file is gone from disk, which is
     * clearer than the raw stream failure a missing path would otherwise cause.
     */
    private function authorizeAccess(ApplicationRequirement $requirement): void
    {
        $isOwner = optional($requirement->applicationable)->client_user_id === Auth::id();

        abort_unless(Gate::allows('view-applications') || $isOwner, 403);

        abort_unless(
            $requirement->file_path && Storage::disk('public')->exists($requirement->file_path),
            404,
            'The uploaded file is no longer available.'
        );
    }
}
