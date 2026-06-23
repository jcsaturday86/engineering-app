<?php

namespace App\Http\Controllers;

use App\Exports\CollectionReportExport;
use App\Exports\PermitReportExport;
use App\Exports\RevenueReportExport;
use App\Models\Application;
use App\Models\Collection;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function permits()
    {
        return view('reports.permits');
    }

    public function revenue()
    {
        return view('reports.revenue');
    }

    public function collections()
    {
        return view('reports.collections');
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:permits,revenue,collections',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:pdf,excel',
        ]);

        $dateFrom = $validated['date_from'];
        $dateTo = $validated['date_to'];
        $filename = "report_{$validated['report_type']}_{$dateFrom}_{$dateTo}";

        if ($validated['format'] === 'excel') {
            $export = match ($validated['report_type']) {
                'permits' => new PermitReportExport($dateFrom, $dateTo),
                'revenue' => new RevenueReportExport($dateFrom, $dateTo),
                'collections' => new CollectionReportExport($dateFrom, $dateTo),
            };

            return Excel::download($export, "{$filename}.xlsx");
        }

        $data = match ($validated['report_type']) {
            'permits' => Application::with('permitType')
                ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->orderBy('created_at')->get(),
            default => Collection::with('application.permitType')
                ->where('status', 'active')
                ->whereBetween('or_date', [$dateFrom, $dateTo])
                ->orderBy('or_date')->get(),
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'data' => $data,
            'reportType' => $validated['report_type'],
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("{$filename}.pdf");
    }
}
