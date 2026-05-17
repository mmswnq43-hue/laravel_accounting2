@extends('layouts.app')
@section('title', 'الحد الأدنى وتنبيهات إعادة الطلب')
@section('content')
<x-report-shell
    title="الحد الأدنى وتنبيهات إعادة الطلب"
    subtitle="بيانات فورية — المنتجات التي تحتاج إلى إعادة طلب"
    :export-route="route('reports.inventory.low-stock.export')"
>
    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-boxes"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['total']) }}</div>
                <div class="rpt-stat-label">إجمالي المنتجات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['out_of_stock']) }}</div>
                <div class="rpt-stat-label">نفد المخزون</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['low_stock']) }}</div>
                <div class="rpt-stat-label">تحت الحد الأدنى</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['normal']) }}</div>
                <div class="rpt-stat-label">مستوى طبيعي</div>
            </div>
        </div>
    </x-slot:kpis>

    {{-- Out of Stock Section --}}
    @if($outOfStock->count() > 0)
        <div class="card-header bg-danger-subtle d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-danger">
                <i class="fas fa-times-circle me-2"></i>نفد المخزون تماماً
            </h6>
            <span class="badge bg-danger">{{ $outOfStock->count() }} منتج</span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الفئة</th>
                        <th class="text-end">الكمية الحالية</th>
                        <th class="text-end">الحد الأدنى</th>
                        <th class="text-end">الفرق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outOfStock as $p)
                    <tr class="table-danger">
                        <td class="fw-semibold">
                            {{ $p->name }}
                            @if($p->code)
                                <small class="text-muted ms-1">({{ $p->code }})</small>
                            @endif
                        </td>
                        <td>{{ $p->category?->name ?? '-' }}</td>
                        <td class="text-end rpt-negative fw-bold">{{ number_format($p->stock_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format($p->min_stock, 2) }}</td>
                        <td class="text-end rpt-negative">{{ number_format((float)$p->stock_quantity - (float)$p->min_stock, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Low Stock Section --}}
    @if($lowStock->count() > 0)
        <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center {{ $outOfStock->count() > 0 ? 'border-top' : '' }}">
            <h6 class="mb-0 fw-bold text-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>تحت الحد الأدنى
            </h6>
            <span class="badge bg-warning text-dark">{{ $lowStock->count() }} منتج</span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الفئة</th>
                        <th class="text-end">الكمية الحالية</th>
                        <th class="text-end">الحد الأدنى</th>
                        <th class="text-end">الفرق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStock as $p)
                    <tr class="table-warning">
                        <td class="fw-semibold">
                            {{ $p->name }}
                            @if($p->code)
                                <small class="text-muted ms-1">({{ $p->code }})</small>
                            @endif
                        </td>
                        <td>{{ $p->category?->name ?? '-' }}</td>
                        <td class="text-end fw-bold" style="color:#d97706;">{{ number_format($p->stock_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format($p->min_stock, 2) }}</td>
                        <td class="text-end" style="color:#d97706;">{{ number_format((float)$p->stock_quantity - (float)$p->min_stock, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- All normal --}}
    @if($outOfStock->count() === 0 && $lowStock->count() === 0)
        <div class="card-body">
            <div class="rpt-empty py-5">
                <i class="fas fa-check-circle d-block mb-3 text-success" style="font-size:3rem; opacity:0.7;"></i>
                <h5 class="text-success mb-2">جميع المنتجات بمستوى طبيعي</h5>
                <p class="text-muted">لا توجد منتجات تحتاج إلى إعادة طلب حالياً</p>
            </div>
        </div>
    @endif
</x-report-shell>
@endsection
