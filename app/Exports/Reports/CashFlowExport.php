<?php

declare(strict_types=1);

namespace App\Exports\Reports;

use App\Exports\BaseReportExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CashFlowExport extends BaseReportExport implements WithColumnFormatting
{
    private Collection $rows;

    public function __construct(Collection $rows, string $companyName, string $currency, string $dateRange)
    {
        parent::__construct($companyName, 'تقرير التدفقات النقدية', $currency, $dateRange);
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    protected function columnHeadings(): array
    {
        return [
            'التاريخ',
            'الفئة',
            'الاتجاه',
            'المرجع',
            'المبلغ',
            'الملاحظات',
        ];
    }

    public function map($row): array
    {
        return $row;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
