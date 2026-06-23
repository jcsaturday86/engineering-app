<?php

namespace App\Exports;

use App\Models\Application;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PermitReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private string $dateFrom, private string $dateTo) {}

    public function collection()
    {
        return Application::with('permitType')
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Application No.', 'Permit Type', 'Applicant', 'Project Title', 'Status', 'Total Est. Cost', 'Date'];
    }

    public function map($app): array
    {
        return [
            $app->application_number,
            $app->permitType->name ?? '',
            $app->applicant_last_name . ', ' . $app->applicant_first_name,
            $app->project_title ?? '',
            ucfirst(str_replace('_', ' ', $app->status)),
            number_format($app->total_estimated_cost, 2),
            $app->created_at->format('M d, Y'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
