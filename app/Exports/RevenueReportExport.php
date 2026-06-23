<?php

namespace App\Exports;

use App\Models\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private string $dateFrom, private string $dateTo) {}

    public function collection()
    {
        return Collection::with('application.permitType')
            ->where('status', 'active')
            ->whereBetween('or_date', [$this->dateFrom, $this->dateTo])
            ->orderBy('or_date')
            ->get();
    }

    public function headings(): array
    {
        return ['OR Number', 'Date', 'Permit Type', 'Applicant', 'Amount Due', 'Amount Received', 'Payment Mode'];
    }

    public function map($collection): array
    {
        return [
            $collection->or_number,
            $collection->or_date instanceof \Carbon\Carbon ? $collection->or_date->format('M d, Y') : $collection->or_date,
            $collection->application?->permitType?->name ?? '',
            $collection->paid_by,
            number_format($collection->amount_due, 2),
            number_format($collection->amount_received, 2),
            ucfirst($collection->payment_mode),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
