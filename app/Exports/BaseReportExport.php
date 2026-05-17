<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class BaseReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize
{
    protected string $companyName;
    protected string $reportTitle;
    protected string $currency;
    protected string $dateRange;

    public function __construct(
        string $companyName,
        string $reportTitle,
        string $currency,
        string $dateRange,
    ) {
        $this->companyName = $companyName;
        $this->reportTitle = $reportTitle;
        $this->currency    = $currency;
        $this->dateRange   = $dateRange;
    }

    /**
     * Subclass provides the column header labels (row 5 in the sheet).
     */
    abstract protected function columnHeadings(): array;

    /**
     * Subclass maps each data row to an array of cell values.
     */
    abstract public function map($row): array;

    /**
     * Subclass provides the data collection.
     */
    abstract public function collection();

    // ─── Concrete implementations ─────────────────────────────────────────

    public function headings(): array
    {
        return [
            [$this->companyName],          // row 1
            [$this->reportTitle],           // row 2
            [$this->dateRange],             // row 3
            [''],                           // row 4 — spacer
            $this->columnHeadings(),        // row 5 — column headers
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);

        // Company name — large bold
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Report title — bold
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

        // Date range — muted italic
        $sheet->getStyle('A3')->getFont()->setItalic(true);
        $sheet->getStyle('A3')->getFont()->getColor()->setARGB('FF74859A');

        // Column header row (row 5) — bold + light blue fill
        $colCount  = count($this->columnHeadings());
        $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $headerRef = "A5:{$lastCol}5";

        $sheet->getStyle($headerRef)->getFont()->setBold(true);
        $sheet->getStyle($headerRef)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFDEEAF6');

        // Freeze pane so column headers stay visible while scrolling
        $sheet->freezePane('A6');

        return [];
    }
}
