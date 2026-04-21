<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class CustomerStatementExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    protected $data;
    protected $company;
    protected $customer;
    protected $dateRange;
    protected $rowIndex = 1;

    public function __construct($data, $company, $customer, $dateRange)
    {
        $this->data = $collection = collect($data['rows']);
        $this->company = $company;
        $this->customer = $customer;
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['المؤسسة:', $this->company->name],
            ['العميل:', $this->customer->name],
            ['الفترة:', $this->dateRange],
            [''], // Spacer
            ['التاريخ', 'المرجع', 'نوع العملية / الوصف', 'مدين', 'دائن', 'الرصيد التراكمي']
        ];
    }

    public function map($row): array
    {
        $this->rowIndex++;
        
        // Data rows start from index 5 in headings (plus 1 for excel 1-based)
        // Header rows: 1(Company), 2(Customer), 3(Period), 4(Spacer), 5(Table Head)
        // Data starts at row 6.
        $currentRow = $this->rowIndex + 4; 

        if ($row['type'] === 'رصيد مدور') {
            return [
                $row['date'],
                $row['reference'],
                $row['type'],
                $row['debit'],
                $row['credit'],
                $row['balance'], // Opening balance is static
            ];
        }

        // Running balance formula: PreviousBalance + Debit - Credit
        // Column mapping: A(Date), B(Ref), C(Type), D(Debit), E(Credit), F(Balance)
        $prevRow = $currentRow - 1;
        $formula = "=F{$prevRow}+D{$currentRow}-E{$currentRow}";

        return [
            $row['date'],
            $row['reference'],
            $row['type'],
            $row['debit'],
            $row['credit'],
            $formula,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0.00',
            'E' => '#,##0.00',
            'F' => '#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling headers
        $sheet->getStyle('1:3')->getFont()->setBold(true);
        $sheet->getStyle('5')->getFont()->setBold(true);
        $sheet->getStyle('5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDEEAF6');
        
        // Freeze header row
        $sheet->freezePane('A6');

        // RTL Support
        $sheet->setRightToLeft(true);

        return [
            5 => ['font' => ['bold' => true]],
        ];
    }
}
