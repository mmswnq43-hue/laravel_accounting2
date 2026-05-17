@extends('layouts.app')
@section('title', 'تقييم المخزون الحالي')
@section('content')
<x-report-shell
    title="تقييم المخزون الحالي"
    subtitle="قيمة المخزون الحالية بناءً على سعر التكلفة"
    :export-route="route('reports.inventory.valuation.export')"
>
    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-warehouse"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['total_value'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي قيمة المخزون ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-boxes"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['total_products']) }}</div>
                <div class="rpt-stat-label">عدد المنتجات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-cubes"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['total_qty'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي الكميات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['low_stock_count']) }}</div>
                <div class="rpt-stat-label">منتجات تحت الحد الأدنى</div>
            </div>
        </div>
    </x-slot:kpis>

    <x-slot:chart>
        <h6 class="fw-bold mb-3">توزيع قيمة المخزون حسب الفئة</h6>
        <div class="row">
            <div class="col-md-4">
                <canvas id="catChart" height="220"></canvas>
            </div>
            <div class="col-md-8">
                <table class="table rpt-table mb-0">
                    <thead>
                        <tr>
                            <th>الفئة</th>
                            <th class="text-end">عدد المنتجات</th>
                            <th class="text-end">الكمية</th>
                            <th class="text-end">القيمة ({{ $company->currency }})</th>
                            <th class="text-end">النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byCategory as $cat)
                        <tr>
                            <td>{{ $cat['name'] }}</td>
                            <td class="text-end">{{ $cat['count'] }}</td>
                            <td class="text-end">{{ number_format($cat['qty'], 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($cat['value'], 2) }}</td>
                            <td class="text-end">
                                {{ $summary['total_value'] > 0 ? number_format(($cat['value'] / $summary['total_value']) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        new Chart(document.getElementById('catChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($byCategory->pluck('name')->toArray()) !!},
                datasets: [{
                    data: {!! json_encode($byCategory->pluck('value')->toArray()) !!},
                    backgroundColor: ['#2563eb','#10b981','#f59e0b','#ef4444','#9333ea','#ea580c','#06b6d4'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        </script>
    </x-slot:chart>

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">تفاصيل المخزون</h6>
        <small class="text-muted">{{ $products->count() }} منتج</small>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>المنتج</th>
                    <th>الفئة</th>
                    <th class="text-end">الكمية</th>
                    <th class="text-end">سعر التكلفة</th>
                    <th class="text-end">قيمة المخزون</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                <tr class="{{ (float)$p->stock_quantity <= 0 ? 'table-danger' : ((float)$p->stock_quantity <= (float)$p->min_stock ? 'table-warning' : '') }}">
                    <td><small class="text-muted">{{ $p->code ?? '-' }}</small></td>
                    <td class="fw-semibold">{{ $p->name }}</td>
                    <td>{{ $p->category?->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($p->stock_quantity, 2) }}</td>
                    <td class="text-end">{{ number_format($p->cost_price, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($p->inventory_value, 2) }}</td>
                    <td>
                        @if((float)$p->stock_quantity <= 0)
                            <span class="badge bg-danger-subtle text-danger">نفد المخزون</span>
                        @elseif((float)$p->stock_quantity <= (float)$p->min_stock)
                            <span class="badge bg-warning-subtle text-warning">تحت الحد الأدنى</span>
                        @else
                            <span class="badge bg-success-subtle text-success">طبيعي</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="rpt-empty">
                        <i class="fas fa-inbox d-block mb-2"></i>لا توجد منتجات
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-report-shell>
@endsection
