<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Exports\Reports\CashFlowExport;
use App\Exports\Reports\ArAgingExport;
use App\Exports\Reports\ApAgingExport;
use App\Exports\Reports\ExpenseAnalysisExport;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends ReportController
{
    // ── Cash Flow ────────────────────────────────────────────────────────────

    public function cashFlow(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $payments = Payment::forCompany($company->id)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $operatingIn  = $payments->where('payment_category', 'invoice_receipt')->sum('amount');
        $operatingOut = $payments->whereIn('payment_category', ['purchase_payment', 'supplier_payment'])->sum('amount');

        $expenses = Expense::forCompany($company->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', 'paid')
            ->sum('total');

        $netOperating = $operatingIn - $operatingOut - $expenses;
        $netCash      = $netOperating; // investing + financing = 0 until those modules exist

        // Monthly breakdown for chart
        $monthly = [];
        $current = $from->copy()->startOfMonth();
        while ($current <= $to) {
            $monthKey       = $current->format('Y-m');
            $monthPayments  = $payments->filter(fn($p) => str_starts_with($p->payment_date, $monthKey));
            $monthly[] = [
                'label' => $current->format('m/Y'),
                'in'    => $monthPayments->where('payment_direction', 'in')->sum('amount'),
                'out'   => $monthPayments->where('payment_direction', 'out')->sum('amount'),
            ];
            $current->addMonth();
        }

        $summary = compact('operatingIn', 'operatingOut', 'expenses', 'netOperating', 'netCash');

        return view('reports.financial.cash_flow', compact('company', 'payments', 'monthly', 'summary', 'from', 'to'));
    }

    public function exportCashFlow(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $payments = Payment::forCompany($company->id)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $expenseRows = Expense::forCompany($company->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', 'paid')
            ->get();

        $mapped = collect();

        foreach ($payments as $p) {
            $mapped->push([
                $p->payment_date,
                $p->payment_category,
                $p->payment_direction === 'in' ? 'داخل' : 'خارج',
                $p->reference ?? '-',
                number_format((float)$p->amount, 2),
                $p->notes ?? '-',
            ]);
        }

        foreach ($expenseRows as $e) {
            $mapped->push([
                $e->expense_date,
                'مصروف',
                'خارج',
                $e->name,
                number_format((float)$e->total, 2),
                '-',
            ]);
        }

        $mapped = $mapped->sortBy(fn($row) => $row[0])->values();

        return Excel::download(
            new CashFlowExport($mapped, $company->name, $company->currency, $this->dateRangeLabel($from, $to)),
            'cash-flow.xlsx'
        );
    }

    // ── AR Aging ─────────────────────────────────────────────────────────────

    public function arAging(Request $request): View
    {
        $company = $this->company($request);
        $today   = now();

        $invoices = Invoice::forCompany($company->id)
            ->where('balance_due', '>', 0)
            ->with('customer')
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current'  => collect(),
            '1_30'     => collect(),
            '31_60'    => collect(),
            '61_90'    => collect(),
            '90_plus'  => collect(),
        ];

        foreach ($invoices as $invoice) {
            if (!$invoice->due_date) {
                $buckets['current']->push($invoice);
                continue;
            }
            $days = (int) $today->diffInDays($invoice->due_date, false);
            if ($days >= 0)       $buckets['current']->push($invoice);
            elseif ($days >= -30) $buckets['1_30']->push($invoice);
            elseif ($days >= -60) $buckets['31_60']->push($invoice);
            elseif ($days >= -90) $buckets['61_90']->push($invoice);
            else                  $buckets['90_plus']->push($invoice);
        }

        $totalAR = $invoices->sum('balance_due');

        $byCustomer = $invoices->groupBy('customer_id')->map(function ($group) {
            return [
                'customer'   => $group->first()->customer,
                'total'      => $group->sum('balance_due'),
                'count'      => $group->count(),
                'oldest_due' => $group->min('due_date'),
            ];
        })->sortByDesc('total')->values()->take(50);

        return view('reports.financial.ar_aging', compact('company', 'buckets', 'totalAR', 'byCustomer'));
    }

    public function exportArAging(Request $request)
    {
        $company = $this->company($request);
        $today   = now();

        $invoices = Invoice::forCompany($company->id)
            ->where('balance_due', '>', 0)
            ->with('customer')
            ->orderBy('due_date')
            ->get();

        $mapped = collect();

        foreach ($invoices as $invoice) {
            $agingBucket = 'جارية';
            if ($invoice->due_date) {
                $days = (int) $today->diffInDays($invoice->due_date, false);
                if ($days >= 0)       $agingBucket = 'جارية';
                elseif ($days >= -30) $agingBucket = '1-30 يوم';
                elseif ($days >= -60) $agingBucket = '31-60 يوم';
                elseif ($days >= -90) $agingBucket = '61-90 يوم';
                else                  $agingBucket = 'أكثر من 90 يوم';
            }

            $mapped->push([
                $invoice->invoice_number,
                $invoice->customer?->name,
                $invoice->invoice_date?->format('Y/m/d'),
                $invoice->due_date?->format('Y/m/d'),
                number_format((float)$invoice->total, 2),
                number_format((float)$invoice->paid_amount, 2),
                number_format((float)$invoice->balance_due, 2),
                $agingBucket,
            ]);
        }

        return Excel::download(
            new ArAgingExport($mapped, $company->name, $company->currency, 'لحظة الآن'),
            'ar-aging.xlsx'
        );
    }

    // ── AP Aging ─────────────────────────────────────────────────────────────

    public function apAging(Request $request): View
    {
        $company = $this->company($request);
        $today   = now();

        $purchases = Purchase::forCompany($company->id)
            ->where('balance_due', '>', 0)
            ->with('supplier')
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'current'  => collect(),
            '1_30'     => collect(),
            '31_60'    => collect(),
            '61_90'    => collect(),
            '90_plus'  => collect(),
        ];

        foreach ($purchases as $purchase) {
            if (!$purchase->due_date) {
                $buckets['current']->push($purchase);
                continue;
            }
            $days = (int) $today->diffInDays($purchase->due_date, false);
            if ($days >= 0)       $buckets['current']->push($purchase);
            elseif ($days >= -30) $buckets['1_30']->push($purchase);
            elseif ($days >= -60) $buckets['31_60']->push($purchase);
            elseif ($days >= -90) $buckets['61_90']->push($purchase);
            else                  $buckets['90_plus']->push($purchase);
        }

        $totalAP = $purchases->sum('balance_due');

        $bySupplier = $purchases->groupBy('supplier_id')->map(function ($group) {
            return [
                'supplier'   => $group->first()->supplier,
                'total'      => $group->sum('balance_due'),
                'count'      => $group->count(),
                'oldest_due' => $group->min('due_date'),
            ];
        })->sortByDesc('total')->values()->take(50);

        return view('reports.financial.ap_aging', compact('company', 'buckets', 'totalAP', 'bySupplier', 'purchases'));
    }

    public function exportApAging(Request $request)
    {
        $company = $this->company($request);
        $today   = now();

        $purchases = Purchase::forCompany($company->id)
            ->where('balance_due', '>', 0)
            ->with('supplier')
            ->orderBy('due_date')
            ->get();

        $mapped = collect();

        foreach ($purchases as $purchase) {
            $agingBucket = 'جارية';
            if ($purchase->due_date) {
                $days = (int) $today->diffInDays($purchase->due_date, false);
                if ($days >= 0)       $agingBucket = 'جارية';
                elseif ($days >= -30) $agingBucket = '1-30 يوم';
                elseif ($days >= -60) $agingBucket = '31-60 يوم';
                elseif ($days >= -90) $agingBucket = '61-90 يوم';
                else                  $agingBucket = 'أكثر من 90 يوم';
            }

            $mapped->push([
                $purchase->purchase_number,
                $purchase->supplier?->name,
                $purchase->purchase_date?->format('Y/m/d'),
                $purchase->due_date?->format('Y/m/d'),
                number_format((float)$purchase->total, 2),
                number_format((float)$purchase->paid_amount, 2),
                number_format((float)$purchase->balance_due, 2),
                $agingBucket,
            ]);
        }

        return Excel::download(
            new ApAgingExport($mapped, $company->name, $company->currency, 'لحظة الآن'),
            'ap-aging.xlsx'
        );
    }

    // ── Expense Analysis ─────────────────────────────────────────────────────

    public function expenseAnalysis(Request $request): View
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $expenses = Expense::forCompany($company->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->with('expenseAccount')
            ->orderByDesc('expense_date')
            ->get();

        $byAccount = $expenses->groupBy('expense_account_id')->map(function ($items) {
            $account = $items->first()->expenseAccount;
            return [
                'account_name' => $account?->name_ar ?: ($account?->name ?? 'غير محدد'),
                'account_code' => $account?->code ?? '-',
                'count'        => $items->count(),
                'total'        => $items->sum('total'),
                'tax'          => $items->sum('tax_amount'),
            ];
        })->sortByDesc('total')->values();

        $summary = [
            'total'      => $expenses->sum('total'),
            'count'      => $expenses->count(),
            'avg'        => $expenses->count() > 0
                ? round($expenses->sum('total') / $expenses->count(), 2)
                : 0,
            'categories' => $byAccount->count(),
        ];

        // Monthly trend
        $monthly = $expenses->groupBy(fn($e) => $e->expense_date->format('Y-m'))
            ->map(fn($items) => [
                'label' => $items->first()->expense_date->format('m/Y'),
                'total' => $items->sum('total'),
            ])
            ->sortKeys()
            ->values();

        return view('reports.financial.expenses', compact('company', 'expenses', 'byAccount', 'summary', 'monthly', 'from', 'to'));
    }

    public function exportExpenseAnalysis(Request $request)
    {
        $company = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $expenses = Expense::forCompany($company->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->with('expenseAccount')
            ->orderByDesc('expense_date')
            ->get();

        $mapped = $expenses->map(fn($e) => [
            $e->expense_date->format('Y/m/d'),
            $e->name,
            $e->expenseAccount?->name_ar ?? '-',
            number_format((float)$e->amount, 2),
            number_format((float)$e->tax_amount, 2),
            number_format((float)$e->total, 2),
            $e->status,
        ]);

        return Excel::download(
            new ExpenseAnalysisExport($mapped, $company->name, $company->currency, $this->dateRangeLabel($from, $to)),
            'expense-analysis.xlsx'
        );
    }
}
