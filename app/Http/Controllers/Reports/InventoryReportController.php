<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Exports\Reports\InventoryValuationExport;
use App\Exports\Reports\StockLedgerExport;
use App\Exports\Reports\LowStockExport;
use App\Exports\Reports\SlowMovingStockExport;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends ReportController
{
    // ─── Inventory Valuation ──────────────────────────────────────────────────

    public function inventoryValuation(Request $request): View
    {
        $company = $this->company($request);

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->with('category')
            ->orderBy('name')
            ->get();

        $products = $products->map(function ($p) {
            $p->inventory_value = (float) $p->stock_quantity * (float) $p->cost_price;
            return $p;
        });

        $byCategory = $products->groupBy(fn ($p) => $p->category?->name ?? 'غير مصنف')
            ->map(fn ($items) => [
                'name'  => $items->first()->category?->name ?? 'غير مصنف',
                'count' => $items->count(),
                'value' => $items->sum('inventory_value'),
                'qty'   => $items->sum('stock_quantity'),
            ])->sortByDesc('value')->values();

        $summary = [
            'total_value'    => $products->sum('inventory_value'),
            'total_products' => $products->count(),
            'total_qty'      => $products->sum(fn ($p) => (float) $p->stock_quantity),
            'low_stock_count' => $products->filter(fn ($p) => (float) $p->stock_quantity <= (float) $p->min_stock)->count(),
        ];

        return view('reports.inventory.valuation', compact('company', 'products', 'byCategory', 'summary'));
    }

    public function exportInventoryValuation(Request $request)
    {
        $company = $this->company($request);

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->with('category')
            ->orderBy('name')
            ->get();

        $mapped = $products->map(function ($p) {
            $inventoryValue = (float) $p->stock_quantity * (float) $p->cost_price;
            return [
                $p->code ?? '',
                $p->name,
                $p->category?->name ?? 'غير مصنف',
                number_format((float) $p->stock_quantity, 2),
                number_format((float) $p->cost_price, 2),
                number_format($inventoryValue, 2),
            ];
        });

        return Excel::download(
            new InventoryValuationExport(
                collect($mapped),
                $company->name,
                $company->currency,
                now()->format('Y/m/d')
            ),
            'inventory-valuation.xlsx'
        );
    }

    // ─── Stock Ledger ─────────────────────────────────────────────────────────

    public function stockLedger(Request $request): View
    {
        $company   = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $productId      = $request->get('product_id');
        $product        = null;
        $movements      = collect();
        $openingBalance = 0;

        if ($productId) {
            $product = Product::forCompany($company->id)->findOrFail($productId);
            $this->authorizeCompany($product, $company);

            // Opening balance: sum of movements BEFORE $from
            $before = InventoryMovement::forCompany($company->id)
                ->where('product_id', $productId)
                ->where('movement_date', '<', $from->toDateString())
                ->get();

            foreach ($before as $m) {
                $openingBalance += $m->direction === 'in' ? (float) $m->quantity : -(float) $m->quantity;
            }

            $movements = InventoryMovement::forCompany($company->id)
                ->where('product_id', $productId)
                ->whereBetween('movement_date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('movement_date')
                ->orderBy('id')
                ->get();

            // Running balance
            $balance   = $openingBalance;
            $movements = $movements->map(function ($m) use (&$balance) {
                $balance += $m->direction === 'in' ? (float) $m->quantity : -(float) $m->quantity;
                $m->running_balance = $balance;
                return $m;
            });
        }

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('reports.inventory.stock_ledger', compact(
            'company', 'product', 'movements', 'products', 'openingBalance', 'from', 'to'
        ));
    }

    public function exportStockLedger(Request $request)
    {
        $company   = $this->company($request);
        [$from, $to] = $this->resolveDateRange($request);

        $productId = $request->get('product_id');

        if (! $productId) {
            return back()->with('error', 'يرجى تحديد منتج لتصدير كرت الصنف.');
        }

        $product = Product::forCompany($company->id)->findOrFail($productId);
        $this->authorizeCompany($product, $company);

        // Opening balance
        $before         = InventoryMovement::forCompany($company->id)
            ->where('product_id', $productId)
            ->where('movement_date', '<', $from->toDateString())
            ->get();
        $openingBalance = 0;
        foreach ($before as $m) {
            $openingBalance += $m->direction === 'in' ? (float) $m->quantity : -(float) $m->quantity;
        }

        $movements = InventoryMovement::forCompany($company->id)
            ->where('product_id', $productId)
            ->whereBetween('movement_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        $balance = $openingBalance;
        $mapped  = $movements->map(function ($m) use (&$balance) {
            $balance += $m->direction === 'in' ? (float) $m->quantity : -(float) $m->quantity;
            return [
                $m->movement_date instanceof \Carbon\Carbon ? $m->movement_date->format('Y-m-d') : $m->movement_date,
                $m->movement_type,
                $m->direction === 'in' ? 'وارد' : 'صادر',
                $m->reference_number ?? '',
                number_format((float) $m->quantity, 2),
                number_format((float) $m->unit_cost, 2),
                number_format((float) $m->total_cost, 2),
                number_format($balance, 2),
            ];
        });

        return Excel::download(
            new StockLedgerExport(
                collect($mapped),
                $company->name,
                $company->currency,
                $this->dateRangeLabel($from, $to)
            ),
            'stock-ledger.xlsx'
        );
    }

    // ─── Low Stock ────────────────────────────────────────────────────────────

    public function lowStock(Request $request): View
    {
        $company = $this->company($request);

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->with('category')
            ->orderBy('stock_quantity')
            ->get();

        $outOfStock = $products->filter(fn ($p) => (float) $p->stock_quantity <= 0);
        $lowStock   = $products->filter(fn ($p) => (float) $p->stock_quantity > 0 && (float) $p->stock_quantity <= (float) $p->min_stock);
        $normal     = $products->filter(fn ($p) => (float) $p->stock_quantity > (float) $p->min_stock);

        $summary = [
            'total'       => $products->count(),
            'out_of_stock' => $outOfStock->count(),
            'low_stock'   => $lowStock->count(),
            'normal'      => $normal->count(),
        ];

        return view('reports.inventory.low_stock', compact(
            'company', 'outOfStock', 'lowStock', 'normal', 'summary'
        ));
    }

    public function exportLowStock(Request $request)
    {
        $company = $this->company($request);

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->with('category')
            ->orderBy('stock_quantity')
            ->get();

        $mapped = $products->map(function ($p) {
            $qty  = (float) $p->stock_quantity;
            $min  = (float) $p->min_stock;
            $diff = $qty - $min;

            if ($qty <= 0) {
                $status = 'نفد المخزون';
            } elseif ($qty <= $min) {
                $status = 'تحت الحد الأدنى';
            } else {
                $status = 'طبيعي';
            }

            return [
                $p->name,
                $p->category?->name ?? 'غير مصنف',
                number_format($qty, 2),
                number_format($min, 2),
                number_format($diff, 2),
                $status,
            ];
        });

        return Excel::download(
            new LowStockExport(
                collect($mapped),
                $company->name,
                $company->currency,
                now()->format('Y/m/d')
            ),
            'low-stock.xlsx'
        );
    }

    // ─── Slow Moving Stock ────────────────────────────────────────────────────

    public function slowMoving(Request $request): View
    {
        $company = $this->company($request);
        $days    = max(1, (int) $request->get('days', 30));
        $cutoff  = now()->subDays($days)->toDateString();

        $lastMovements = InventoryMovement::forCompany($company->id)
            ->selectRaw('product_id, MAX(movement_date) as last_moved')
            ->groupBy('product_id')
            ->pluck('last_moved', 'product_id');

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->where('stock_quantity', '>', 0)
            ->with('category')
            ->get()
            ->map(function ($p) use ($lastMovements, $cutoff) {
                $last           = $lastMovements[$p->id] ?? null;
                $p->last_moved  = $last;
                $p->days_idle   = $last ? (int) now()->diffInDays(\Carbon\Carbon::parse($last)) : null;
                $p->inventory_value = (float) $p->stock_quantity * (float) $p->cost_price;
                return $p;
            })
            ->filter(fn ($p) => is_null($p->last_moved) || $p->last_moved < $cutoff)
            ->sortByDesc('days_idle')
            ->values();

        $summary = [
            'slow_count'    => $products->count(),
            'total_value'   => $products->sum('inventory_value'),
            'avg_idle_days' => $products->isNotEmpty()
                ? round($products->whereNotNull('days_idle')->avg('days_idle'))
                : 0,
        ];

        return view('reports.inventory.slow_moving', compact('company', 'products', 'summary', 'days'));
    }

    public function exportSlowMoving(Request $request)
    {
        $company = $this->company($request);
        $days    = max(1, (int) $request->get('days', 30));
        $cutoff  = now()->subDays($days)->toDateString();

        $lastMovements = InventoryMovement::forCompany($company->id)
            ->selectRaw('product_id, MAX(movement_date) as last_moved')
            ->groupBy('product_id')
            ->pluck('last_moved', 'product_id');

        $products = Product::forCompany($company->id)
            ->where('type', 'product')
            ->where('stock_quantity', '>', 0)
            ->with('category')
            ->get()
            ->map(function ($p) use ($lastMovements, $cutoff) {
                $last               = $lastMovements[$p->id] ?? null;
                $p->last_moved      = $last;
                $p->days_idle       = $last ? (int) now()->diffInDays(\Carbon\Carbon::parse($last)) : null;
                $p->inventory_value = (float) $p->stock_quantity * (float) $p->cost_price;
                return $p;
            })
            ->filter(fn ($p) => is_null($p->last_moved) || $p->last_moved < $cutoff)
            ->sortByDesc('days_idle')
            ->values();

        $mapped = $products->map(fn ($p) => [
            $p->name,
            $p->category?->name ?? 'غير مصنف',
            $p->last_moved ?? 'لا توجد حركة',
            $p->days_idle !== null ? $p->days_idle : '-',
            number_format((float) $p->stock_quantity, 2),
            number_format($p->inventory_value, 2),
        ]);

        return Excel::download(
            new SlowMovingStockExport(
                collect($mapped),
                $company->name,
                $company->currency,
                now()->format('Y/m/d')
            ),
            'slow-moving-stock.xlsx'
        );
    }
}
