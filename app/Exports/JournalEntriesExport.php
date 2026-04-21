<?php

namespace App\Exports;

use App\Models\JournalEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalEntriesExport implements FromView, ShouldAutoSize, WithStyles
{
    private Collection $entries;
    private string $companyName;
    private string $currency;

    public function __construct(Collection $entries, string $companyName, string $currency)
    {
        $this->entries = $entries;
        $this->companyName = $companyName;
        $this->currency = $currency;
    }

    public function view(): View
    {
        return view('exports.journal_entries', [
            'entries' => $this->entries,
            'companyName' => $this->companyName,
            'currency' => $this->currency,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}
