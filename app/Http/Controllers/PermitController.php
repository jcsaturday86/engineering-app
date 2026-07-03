<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\OccupancyApplication;
use App\Models\Permit;
use App\Models\PermitType;
use App\Models\Signatory;
use App\Notifications\ApplicationApprovedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermitController extends Controller
{
    public function buildingIndex()
    {
        $applications = Application::with('permitType', 'permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released'])
            ->latest()
            ->paginate(20);

        $type = 'building';
        return view('permits.index', compact('applications', 'type'));
    }

    public function occupancyIndex()
    {
        $applications = OccupancyApplication::with('applicationType', 'permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released'])
            ->latest()
            ->paginate(20);

        $type = 'occupancy';
        return view('permits.index', compact('applications', 'type'));
    }

    // BP permit generation
    public function generate(Application $application)
    {
        return $this->doGenerate($application, 'BP');
    }

    // OP permit generation
    public function generateOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doGenerate($occupancyApplication, 'OP');
    }

    private function doGenerate(PermitApplicationContract $application, string $permitCode)
    {
        if ($application->status !== 'paid') {
            return back()->with('error', 'Application must be paid before generating permit.');
        }

        $permitType = PermitType::where('code', $permitCode)->firstOrFail();
        $morphType = $permitCode === 'OP' ? 'op' : 'bp';

        DB::transaction(function () use ($application, $permitType, $permitCode, $morphType) {
            $counter = Permit::where('permit_type_id', $permitType->id)
                    ->where('permit_year', now()->year)
                    ->count() + 1;

            $permitNumber = sprintf('%s-%s-%s-%05d', $permitCode, now()->format('Y'), now()->format('m'), $counter);

            Permit::create([
                'applicationable_type' => $morphType,
                'applicationable_id' => $application->id,
                'application_id' => $application->id,
                'permit_type_id' => $permitType->id,
                'permit_year' => now()->year,
                'permit_month' => now()->month,
                'permit_counter' => $counter,
                'permit_number' => $permitNumber,
                'issued_date' => now()->toDateString(),
                'processed_by' => Auth::id(),
                'status' => 'generated',
            ]);

            $application->update([
                'status' => 'permit_generated',
                'issued_date' => now()->toDateString(),
            ]);

            activity()->causedBy(Auth::user())->performedOn($application)->log('Permit generated');
        });

        if ($application->client_user_id) {
            $permit = Permit::where('applicationable_type', $morphType)
                ->where('applicationable_id', $application->id)
                ->latest()->first();
            $application->clientUser->notify(new ApplicationApprovedNotification($application, $permit));
        }

        return back()->with('success', 'Permit generated successfully.');
    }

    public function print(Permit $permit)
    {
        $permit->load('applicationable', 'permitType');

        $application = $permit->applicationable;
        $application->load(
            'applicationType',
            'applicantProvince', 'applicantCity',
            'applicantBarangay', 'buildingBarangay',
            'formOfOwnership',
            'applicationOccupancyGroups.occupancyGroup',
            'applicationOccupancyGroups.occupancySubGroup',
            'collections.collectionDetails'
        );

        // Load BP-specific relations if available
        if ($application instanceof Application) {
            $application->load('scopeOfWork');
        }

        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');
        $settings = \App\Models\Setting::where('group', 'general')->pluck('value', 'key');

        $sealImage = null;
        if (! empty($settings['general.logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['general.logo'])) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($settings['general.logo']);
            $sealImage = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($settings['general.logo']));
        }

        $template = $permit->permitType->code === 'OP' ? 'pdf.occupancy-permit' : 'pdf.building-permit';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, compact('permit', 'application', 'signatories', 'settings', 'sealImage'));
        $pdf->setPaper('a4', $template === 'pdf.building-permit' ? 'portrait' : 'landscape');

        return $pdf->stream("permit_{$permit->permit_number}.pdf");
    }

    public function zoningCertification(Application $application)
    {
        $application->load('zoningAssessment', 'collections.collectionDetails');
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.zoning-certification', compact('application', 'signatories'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("zoning_cert_{$application->application_number}.pdf");
    }

    public function locationalClearance(Application $application)
    {
        $application->load('zoningAssessment', 'collections.collectionDetails');
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.locational-clearance', compact('application', 'signatories'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("locational_{$application->application_number}.pdf");
    }

    public function evaluationReport(Application $application)
    {
        $application->load('zoningAssessment');
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.evaluation-report', compact('application', 'signatories'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("evaluation_{$application->application_number}.pdf");
    }
}
