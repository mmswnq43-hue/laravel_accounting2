<?php
declare(strict_types=1);

namespace App\Exports\Reports;

use App\Exports\BaseReportExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class VatReturnExport extends BaseReportExport implements WithColumnFormatting
{
    private Collection $rows;

    public function __construct(Collection $rows, string $companyName, string $currency, string $dateRange)
    {
        parent::__construct($companyName, 'الإقرار الضريبي - ضريبة القيمة المضافة', $currency, $dateRange);
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    protected function columnHeadings(): array
    {
        return ['نوع', 'نسبة الضريبة %', 'الأساس الخاضع', 'مبلغ الضريبة', 'عدد المعاملات'];
    }

    public function map($row): array
    {
        return is_array($row) ? $row : (array) $row;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0.00',
            'D' => '#,##0.00',
        ];
    }
}
