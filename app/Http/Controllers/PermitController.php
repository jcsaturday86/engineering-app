<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Permit;
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
            ->whereHas('permitType', fn ($q) => $q->where('code', 'BP'))
            ->latest()
            ->paginate(20);

        $type = 'building';
        return view('permits.index', compact('applications', 'type'));
    }

    public function occupancyIndex()
    {
        $applications = Application::with('permitType', 'permits')
            ->whereIn('status', ['paid', 'permit_generated', 'released'])
            ->whereHas('permitType', fn ($q) => $q->where('code', 'OP'))
            ->latest()
            ->paginate(20);

        $type = 'occupancy';
        return view('permits.index', compact('applications', 'type'));
    }

    public function generate(Application $application)
    {
        if ($application->status !== 'paid') {
            return back()->with('error', 'Application must be paid before generating permit.');
        }

        DB::transaction(function () use ($application) {
            $counter = Permit::where('permit_type_id', $application->permit_type_id)
                    ->where('permit_year', now()->year)
                    ->count() + 1;

            $prefix = $application->permitType->code;
            $permitNumber = sprintf('%s-%s-%s-%05d', $prefix, now()->format('Y'), now()->format('m'), $counter);

            Permit::create([
                'application_id' => $application->id,
                'permit_type_id' => $application->permit_type_id,
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

        // Notify client user if linked
        if ($application->client_user_id) {
            $permit = Permit::where('application_id', $application->id)->latest()->first();
            $application->clientUser->notify(new ApplicationApprovedNotification($application, $permit));
        }

        return back()->with('success', 'Permit generated successfully.');
    }

    public function print(Permit $permit)
    {
        $permit->load('application.permitType', 'application.applicationType',
            'application.applicantProvince', 'application.applicantCity',
            'application.applicantBarangay', 'application.buildingBarangay',
            'application.scopeOfWork', 'application.formOfOwnership',
            'application.applicationOccupancyGroups.occupancyGroup',
            'application.applicationOccupancyGroups.occupancySubGroup',
            'application.collections.collectionDetails'
        );

        $application = $permit->application;
        $signatories = Signatory::where('is_active', true)->get()->keyBy('role');
        $template = $permit->permitType->code === 'OP' ? 'pdf.occupancy-permit' : 'pdf.building-permit';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, compact('permit', 'application', 'signatories'));
        $pdf->setPaper('a4', 'landscape');

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
