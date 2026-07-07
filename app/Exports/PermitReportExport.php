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
        return Application::with('permitType', 'permits')
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo . ' 23:59:59'])
            ->where(function ($q) {
                $q->where('status', 'permit_generated')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'paid')
                            ->whereHas('permits', function ($q3) {
                                $q3->withTrashed()->where('status', 'revoked');
                            });
                    });
            })
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Application No.', 'Permit No.', 'Permit Type', 'Applicant', 'Project Title', 'Status', 'Total Est. Cost', 'Date', 'TTA'];
    }

    public function map($app): array
    {
        $permit = $app->permits->first();
        $isRevoked = false;
        if (! $permit) {
            $permit = $app->permits()->onlyTrashed()->where('status', 'revoked')->latest('deleted_at')->first();
            $isRevoked = (bool) $permit;
        }

        $tatStart = $app->submitted_at ?? $app->created_at;
        $tatDays = $permit ? (int) floor($tatStart->diffInDays($permit->created_at, true)) : null;
        $permitDate = $permit?->issued_date ?? $permit?->created_at;
        $dateRange = $permitDate ? $app->created_at->format('M d, Y') . ' - ' . $permitDate->format('M d, Y') : $app->created_at->format('M d, Y');

        return [
            $app->application_number,
            $permit->permit_number ?? '-',
            $app->permitType->name ?? '',
            $app->applicant_last_name . ', ' . $app->applicant_first_name,
            $app->project_title ?? '',
            $isRevoked ? 'Permit Revoked' : ucfirst(str_replace('_', ' ', $app->status)),
            number_format($app->total_estimated_cost, 2),
            $dateRange,
            $tatDays !== null ? $tatDays . ' day' . ($tatDays == 1 ? '' : 's') : '–',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
