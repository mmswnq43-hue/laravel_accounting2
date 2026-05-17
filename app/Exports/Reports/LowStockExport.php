<?php

declare(strict_types=1);

namespace App\Exports\Reports;

use App\Exports\BaseReportExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LowStockExport extends BaseReportExport implements WithColumnFormatting
{
    private Collection $rows;

    public function __construct(Collection $rows, string $companyName, string $currency, string $dateRange)
    {
        parent::__construct($companyName, 'الحد الأدنى وتنبيهات إعادة الطلب', $currency, $dateRange);
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    protected function columnHeadings(): array
    {
        return [
            'المنتج',
            'الفئة',
            'الكمية الحالية',
            'الحد الأدنى',
            'الفرق',
            'الحالة',
        ];
    }

    public function map($row): array
    {
        return $row;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
