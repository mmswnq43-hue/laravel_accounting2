@extends('layouts.app')
@section('title', 'ركود وتدوير المخزون')
@section('content')
<x-report-shell
    title="ركود وتدوير المخزون"
    subtitle="المنتجات التي لم تشهد حركة خلال الفترة المحددة"
    :export-route="route('reports.inventory.slow-moving.export', ['days' => $days])"
>
    <x-slot:filters>
        <form method="GET" action="{{ route('reports.inventory.slow-moving') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">فترة الركود</label>
                <select name="days" class="form-select">
                    @foreach([30 => '30 يوم', 60 => '60 يوم', 90 => '90 يوم', 180 => '180 يوم'] as $val => $label)
                        <option value="{{ $val }}" {{ $days == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> تطبيق
                </button>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-4">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['slow_count']) }}</div>
                <div class="rpt-stat-label">منتجات راكدة (أكثر من {{ $days }} يوم)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-money-bill-wave"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['total_value'], 2) }}</div>
                <div class="rpt-stat-label">قيمة المخزون الراكد ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon purple"><i class="fas fa-calendar-times"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['avg_idle_days']) }}</div>
                <div class="rpt-stat-label">متوسط أيام الركود</div>
            </div>
        </div>
    </x-slot:kpis>

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">المنتجات الراكدة</h6>
        <small class="text-muted">{{ $products->count() }} منتج بدون حركة خلال {{ $days }} يوم</small>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الفئة</th>
                    <th>آخر حركة</th>
                    <th class="text-end">أيام الركود</th>
                    <th class="text-end">الكمية</th>
                    <th class="text-end">قيمة المخزون</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                @php
                    $rowClass = '';
                    if ($p->days_idle === null || $p->days_idle > 90) {
                        $rowClass = 'table-danger';
                    } elseif ($p->days_idle > 60) {
                        $rowClass = 'table-warning';
                    } elseif ($p->days_idle > 30) {
                        $rowClass = 'table-info';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="fw-semibold">
                        {{ $p->name }}
                        @if($p->code)
                            <small class="text-muted ms-1">({{ $p->code }})</small>
                        @endif
                    </td>
                    <td>{{ $p->category?->name ?? '-' }}</td>
                    <td>
                        @if($p->last_moved)
                            {{ $p->last_moved }}
                        @else
                            <span class="badge bg-secondary">لا توجد حركة</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($p->days_idle !== null)
                            @if($p->days_idle > 90)
                                <span class="badge bg-danger">{{ $p->days_idle }} يوم</span>
                            @elseif($p->days_idle > 60)
                                <span class="badge bg-warning text-dark">{{ $p->days_idle }} يوم</span>
                            @else
                                <span class="badge bg-info text-dark">{{ $p->days_idle }} يوم</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">—</span>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($p->stock_quantity, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($p->inventory_value, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="rpt-empty">
                        <i class="fas fa-check-circle d-block mb-2 text-success"></i>
                        لا توجد منتجات راكدة خلال {{ $days }} يوم الماضية
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-report-shell>
@endsection
