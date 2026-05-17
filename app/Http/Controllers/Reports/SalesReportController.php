<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Exports\Reports\CustomerAnalyticsExport;
use App\Exports\Reports\ProductProfitabilityExport;
use App\Exports\Reports\SalesByChannelExport;
use App\Exports\Reports\SalesRegisterExport;
use App\Exports\Reports\SalesReturnsExport;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SalesChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends ReportController
{
    // ─── Sales Register ───────────────────────────────────────────────────────

    public function salesRegister(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $query = Invoice::forCompany($company->id)
            ->with(['customer', 'salesChannel', 'branch'])
            ->whereBetween('invoice_date', [$from, $to]);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sales_channel_id')) {
            $query->where('sales_channel_id', $request->sales_channel_id);
        }

        $invoices = (clone $query)->orderByDesc('invoice_date')->paginate(25)->withQueryString();

        $summary = (clone $query)->selectRaw(
            'COUNT(*) as total_count,
             SUM(total) as total_revenue,
             SUM(paid_amount) as total_collected,
             SUM(balance_due) as total_outstanding,
             SUM(tax_amount) as total_tax'
        )->first();

        $customers = Customer::forCompany($company->id)->orderBy('name')->get(['id', 'name']);
        $channels  = SalesChannel::where('company_id', $company->id)->get(['id', 'name']);

        return view('reports.sales.register', compact(
            'company', 'from', 'to', 'invoices', 'summary', 'customers', 'channels'
        ));
    }

    public function exportSalesRegister(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $query = Invoice::forCompany($company->id)
            ->with(['customer', 'salesChannel', 'branch'])
            ->whereBetween('invoice_date', [$from, $to]);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sales_channel_id')) {
            $query->where('sales_channel_id', $request->sales_channel_id);
        }

        $all    = $query->orderByDesc('invoice_date')->get();
        $mapped = $all->map(fn ($invoice) => [
            $invoice->invoice_number,
            $invoice->invoice_date?->format('Y-m-d'),
            $invoice->customer?->name ?? '-',
            $invoice->salesChannel?->name ?? '-',
            $invoice->branch?->name ?? '-',
            number_format((float) $invoice->subtotal, 2),
            number_format((float) $invoice->tax_amount, 2),
            number_format((float) $invoice->total, 2),
            number_format((float) $invoice->paid_amount, 2),
            number_format((float) $invoice->balance_due, 2),
            $invoice->status,
        ]);

        return Excel::download(
            new SalesRegisterExport(
                collect($mapped),
                $company->name,
                $company->currency,
                $this->dateRangeLabel($from, $to)
            ),
            'sales-register.xlsx'
        );
    }

    // ─── Product Profitability ────────────────────────────────────────────────

    public function productProfitability(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $rows = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.company_id', $company->id)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->selectRaw(
                'products.id as product_id,
                 products.name as product_name,
                 products.code as product_code,
                 SUM(invoice_items.quantity) as total_qty,
                 SUM(invoice_items.total) as revenue,
                 SUM(invoice_items.quantity * products.cost_price) as cost,
                 (SUM(invoice_items.total) - SUM(invoice_items.quantity * products.cost_price)) as profit'
            )
            ->groupBy('products.id', 'products.name', 'products.code')
            ->orderByDesc('revenue')
            ->get();

        $rows = $rows->map(function ($row) {
            $row->margin = $row->revenue > 0
                ? round(($row->profit / $row->revenue) * 100, 2)
                : 0;
            return $row;
        });

        $totals = [
            'revenue' => $rows->sum('revenue'),
            'cost'    => $rows->sum('cost'),
            'profit'  => $rows->sum('profit'),
            'margin'  => $rows->count() > 0 ? round($rows->avg('margin'), 2) : 0,
        ];

        return view('reports.sales.profitability', compact('company', 'from', 'to', 'rows', 'totals'));
    }

    public function exportProductProfitability(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $rows = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.company_id', $company->id)
            ->where('invoices.status', '!=', 'draft')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->selectRaw(
                'products.id as product_id,
                 products.name as product_name,
                 products.code as product_code,
                 SUM(invoice_items.quantity) as total_qty,
                 SUM(invoice_items.total) as revenue,
                 SUM(invoice_items.quantity * products.cost_price) as cost,
                 (SUM(invoice_items.total) - SUM(invoice_items.quantity * products.cost_price)) as profit'
            )
            ->groupBy('products.id', 'products.name', 'products.code')
            ->orderByDesc('revenue')
            ->get();

        $mapped = $rows->map(function ($row) {
            $margin = $row->revenue > 0 ? round(($row->profit / $row->revenue) * 100, 2) : 0;
            return [
                $row->product_code,
                $row->product_name,
                number_format((float) $row->total_qty, 2),
                number_format((float) $row->revenue, 2),
                number_format((float) $row->cost, 2),
                number_format((float) $row->profit, 2),
                number_format($margin, 2) . '%',
            ];
        });

        return Excel::download(
            new ProductProfitabilityExport(
                collect($mapped),
                $company->name,
                $company->currency,
                $this->dateRangeLabel($from, $to)
            ),
            'product-profitability.xlsx'
        );
    }

    // ─── Sales By Channel ─────────────────────────────────────────────────────

    public function salesByChannel(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $byChannel = Invoice::forCompany($company->id)
            ->join('sales_channels', 'sales_channels.id', '=', 'invoices.sales_channel_id')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('invoices.status', '!=', 'draft')
            ->selectRaw(
                'sales_channels.id,
                 sales_channels.name,
                 COUNT(invoices.id) as invoice_count,
                 SUM(invoices.total) as total_revenue,
                 SUM(invoices.tax_amount) as total_tax,
                 AVG(invoices.total) as avg_invoice'
            )
            ->groupBy('sales_channels.id', 'sales_channels.name')
            ->orderByDesc('total_revenue')
            ->get();

        $byBranch = Invoice::forCompany($company->id)
            ->join('branches', 'branches.id', '=', 'invoices.branch_id')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('invoices.status', '!=', 'draft')
            ->selectRaw(
                'branches.id,
                 branches.name,
                 COUNT(invoices.id) as invoice_count,
                 SUM(invoices.total) as total_revenue,
                 SUM(invoices.tax_amount) as total_tax,
                 AVG(invoices.total) as avg_invoice'
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_revenue')
            ->get();

        $grandTotal = Invoice::forCompany($company->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'draft')
            ->selectRaw('COUNT(*) as count, SUM(total) as revenue')
            ->first();

        return view('reports.sales.channels', compact(
            'company', 'from', 'to', 'byChannel', 'byBranch', 'grandTotal'
        ));
    }

    public function exportSalesByChannel(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $byChannel = Invoice::forCompany($company->id)
            ->join('sales_channels', 'sales_channels.id', '=', 'invoices.sales_channel_id')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('invoices.status', '!=', 'draft')
            ->selectRaw(
                'sales_channels.id,
                 sales_channels.name,
                 COUNT(invoices.id) as invoice_count,
                 SUM(invoices.total) as total_revenue,
                 SUM(invoices.tax_amount) as total_tax,
                 AVG(invoices.total) as avg_invoice'
            )
            ->groupBy('sales_channels.id', 'sales_channels.name')
            ->orderByDesc('total_revenue')
            ->get();

        $mapped = $byChannel->map(fn ($row) => [
            $row->name,
            $row->invoice_count,
            number_format((float) $row->total_revenue, 2),
            number_format((float) $row->total_tax, 2),
            number_format((float) $row->avg_invoice, 2),
        ]);

        return Excel::download(
            new SalesByChannelExport(
                collect($mapped),
                $company->name,
                $company->currency,
                $this->dateRangeLabel($from, $to)
            ),
            'sales-by-channel.xlsx'
        );
    }

    // ─── Customer Analytics ───────────────────────────────────────────────────

    public function customerAnalytics(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $customers = Invoice::forCompany($company->id)
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('invoices.status', '!=', 'draft')
            ->selectRaw(
                'customers.id,
                 customers.name,
                 customers.phone,
                 customers.email,
                 COUNT(invoices.id) as invoice_count,
                 SUM(invoices.total) as total_purchase,
                 SUM(invoices.paid_amount) as total_paid,
                 SUM(invoices.balance_due) as total_outstanding'
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email')
            ->orderByDesc('total_purchase')
            ->paginate(25)
            ->withQueryString();

        $totalCustomers = Invoice::forCompany($company->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'draft')
            ->distinct('customer_id')
            ->count('customer_id');

        $totalRevenue = Invoice::forCompany($company->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'draft')
            ->sum('total');

        return view('reports.sales.customers', compact(
            'company', 'from', 'to', 'customers', 'totalCustomers', 'totalRevenue'
        ));
    }

    public function exportCustomerAnalytics(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $customers = Invoice::forCompany($company->id)
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('invoices.status', '!=', 'draft')
            ->selectRaw(
                'customers.id,
                 customers.name,
                 customers.phone,
                 customers.email,
                 COUNT(invoices.id) as invoice_count,
                 SUM(invoices.total) as total_purchase,
                 SUM(invoices.paid_amount) as total_paid,
                 SUM(invoices.balance_due) as total_outstanding'
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.email')
            ->orderByDesc('total_purchase')
            ->get();

        $mapped = $customers->map(fn ($row) => [
            $row->name,
            $row->phone ?? '-',
            $row->email ?? '-',
            $row->invoice_count,
            number_format((float) $row->total_purchase, 2),
            number_format((float) $row->total_paid, 2),
            number_format((float) $row->total_outstanding, 2),
        ]);

        return Excel::download(
            new CustomerAnalyticsExport(
                collect($mapped),
                $company->name,
                $company->currency,
                $this->dateRangeLabel($from, $to)
            ),
            'customer-analytics.xlsx'
        );
    }

    // ─── Sales Returns ────────────────────────────────────────────────────────

    public function salesReturns(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $query = Invoice::forCompany($company->id)
            ->with(['customer', 'salesChannel'])
            ->whereIn('status', ['cancelled', 'returned', 'refunded'])
            ->whereBetween('invoice_date', [$from, $to]);

        $invoices = (clone $query)->orderByDesc('invoice_date')->paginate(25)->withQueryString();

        $summary = (clone $query)->selectRaw('COUNT(*) as total_count, SUM(total) as total_value')->first();

        return view('reports.sales.returns', compact('company', 'from', 'to', 'invoices', 'summary'));
    }

    public function exportSalesReturns(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $invoices = Invoice::forCompany($company->id)
            ->with(['customer', 'salesChannel'])
            ->whereIn('status', ['cancelled', 'returned', 'refunded'])
            ->whereBetween('invoice_date', [$from, $to])
            ->orderByDesc('invoice_date')
            ->get();

        $mapped = $invoices->map(fn ($invoice) => [
            $invoice->invoice_number,
            $invoice->invoice_date?->format('Y-m-d'),
            $invoice->customer?->name ?? '-',
            $invoice->salesChannel?->name ?? '-',
            number_format((float) $invoice->total, 2),
            $invoice->status,
            $invoice->notes ?? '-',
        ]);

        return Excel::download(
            new SalesReturnsExport(
                collect($mapped),
                $company->name,
                $company->currency,
                $this->dateRangeLabel($from, $to)
            ),
            'sales-returns.xlsx'
        );
    }
}
