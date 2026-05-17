<?php
declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Exports\Reports\VatReturnExport;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\TaxSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TaxReportController extends ReportController
{
    public function vatReturn(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        // ── Output VAT (Sales) ──────────────────────────────────────
        $salesSummary = Invoice::forCompany($company->id)
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', 'draft')
            ->selectRaw('
                COUNT(*) as invoice_count,
                SUM(subtotal) as taxable_sales,
                SUM(tax_amount) as output_vat,
                SUM(total) as gross_sales
            ')
            ->first();

        // Output VAT breakdown by rate (from invoice items)
        $outputByRate = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.company_id', $company->id)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.invoice_date', [$from->toDateString(), $to->toDateString()])
            ->where('invoice_items.tax_rate', '>', 0)
            ->selectRaw('
                invoice_items.tax_rate,
                SUM(invoice_items.total - invoice_items.tax_amount) as base_amount,
                SUM(invoice_items.tax_amount) as vat_amount,
                COUNT(DISTINCT invoices.id) as transaction_count
            ')
            ->groupBy('invoice_items.tax_rate')
            ->orderBy('invoice_items.tax_rate')
            ->get();

        // Zero-rated / exempt sales (tax_rate = 0)
        $zeroRatedSales = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.company_id', $company->id)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.invoice_date', [$from->toDateString(), $to->toDateString()])
            ->where(fn($q) => $q->where('invoice_items.tax_rate', 0)->orWhereNull('invoice_items.tax_rate'))
            ->selectRaw('SUM(invoice_items.total) as base_amount, COUNT(DISTINCT invoices.id) as transaction_count')
            ->first();

        // ── Input VAT (Purchases) ───────────────────────────────────
        $purchasesSummary = Purchase::forCompany($company->id)
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', 'draft')
            ->selectRaw('
                COUNT(*) as purchase_count,
                SUM(subtotal) as taxable_purchases,
                SUM(tax_amount) as input_vat,
                SUM(total) as gross_purchases
            ')
            ->first();

        // Input VAT breakdown by rate (from purchase items)
        $inputByRate = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.company_id', $company->id)
            ->where('purchases.status', '!=', 'draft')
            ->whereBetween('purchases.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->where('purchase_items.tax_rate', '>', 0)
            ->selectRaw('
                purchase_items.tax_rate,
                SUM(purchase_items.total - purchase_items.tax_amount) as base_amount,
                SUM(purchase_items.tax_amount) as vat_amount,
                COUNT(DISTINCT purchases.id) as transaction_count
            ')
            ->groupBy('purchase_items.tax_rate')
            ->orderBy('purchase_items.tax_rate')
            ->get();

        // ── Net VAT ─────────────────────────────────────────────────
        $outputVat = (float)($salesSummary->output_vat ?? 0);
        $inputVat  = (float)($purchasesSummary->input_vat ?? 0);
        $netVat    = $outputVat - $inputVat;

        $taxSettings = TaxSetting::where('company_id', $company->id)->get();

        return view('reports.tax.vat_return', compact(
            'company', 'from', 'to',
            'salesSummary', 'outputByRate', 'zeroRatedSales',
            'purchasesSummary', 'inputByRate',
            'outputVat', 'inputVat', 'netVat',
            'taxSettings'
        ));
    }

    public function exportVatReturn(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        // Build rows for export: two sections with headers
        $rows = collect();

        // Section 1: Output VAT
        $rows->push(['نوع', 'نسبة الضريبة %', 'الأساس الخاضع', 'مبلغ الضريبة', 'عدد المعاملات']);

        $outputByRate = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.company_id', $company->id)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.invoice_date', [$from->toDateString(), $to->toDateString()])
            ->where('invoice_items.tax_rate', '>', 0)
            ->selectRaw('invoice_items.tax_rate, SUM(invoice_items.total - invoice_items.tax_amount) as base_amount, SUM(invoice_items.tax_amount) as vat_amount, COUNT(DISTINCT invoices.id) as transaction_count')
            ->groupBy('invoice_items.tax_rate')
            ->get();

        foreach ($outputByRate as $row) {
            $rows->push(['ضريبة مخرجات', number_format($row->tax_rate, 2) . '%', number_format($row->base_amount, 2), number_format($row->vat_amount, 2), $row->transaction_count]);
        }

        $inputByRate = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.company_id', $company->id)
            ->where('purchases.status', '!=', 'draft')
            ->whereBetween('purchases.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->where('purchase_items.tax_rate', '>', 0)
            ->selectRaw('purchase_items.tax_rate, SUM(purchase_items.total - purchase_items.tax_amount) as base_amount, SUM(purchase_items.tax_amount) as vat_amount, COUNT(DISTINCT purchases.id) as transaction_count')
            ->groupBy('purchase_items.tax_rate')
            ->get();

        foreach ($inputByRate as $row) {
            $rows->push(['ضريبة مدخلات', number_format($row->tax_rate, 2) . '%', number_format($row->base_amount, 2), number_format($row->vat_amount, 2), $row->transaction_count]);
        }

        return Excel::download(
            new VatReturnExport($rows, $company->name, $company->currency, $this->dateRangeLabel($from, $to)),
            'vat-return.xlsx'
        );
    }
}
