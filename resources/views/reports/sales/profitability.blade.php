@extends('layouts.app')
@section('title', 'ربحية المنتجات')
@section('content')
<x-report-shell
    title="ربحية المنتجات"
    subtitle="مقارنة إيرادات المبيعات بتكلفة المخزون لكل منتج"
    :export-route="route('reports.sales.profitability.export', request()->query())"
>
    <x-slot:filters>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">من تاريخ</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', $from->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', $to->format('Y-m-d')) }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>تطبيق</button>
                <a href="{{ route('reports.sales.profitability') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-chart-line"></i></div>
                <div class="rpt-stat-value">{{ number_format($totals['revenue'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي الإيرادات ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-boxes"></i></div>
                <div class="rpt-stat-value">{{ number_format($totals['cost'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي التكلفة ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="rpt-stat-value">{{ number_format($totals['profit'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي الربح ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon purple"><i class="fas fa-percent"></i></div>
                <div class="rpt-stat-value">{{ number_format($totals['margin'], 2) }}%</div>
                <div class="rpt-stat-label">متوسط هامش الربح</div>
            </div>
        </div>
    </x-slot:kpis>

    <x-slot:chart>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title fw-bold mb-3">أفضل 10 منتجات حسب الإيرادات</h6>
                <canvas id="profitabilityChart" height="100"></canvas>
            </div>
        </div>
        @php
            $top10 = $rows->take(10);
        @endphp
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('profitabilityChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($top10->pluck('product_name')->toArray()) !!},
                    datasets: [
                        {
                            label: 'الإيرادات',
                            data: {!! json_encode($top10->pluck('revenue')->map(fn($v) => round((float)$v, 2))->toArray()) !!},
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                        },
                        {
                            label: 'الربح',
                            data: {!! json_encode($top10->pluck('profit')->map(fn($v) => round((float)$v, 2))->toArray()) !!},
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
        </script>
    </x-slot:chart>

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">تفاصيل ربحية المنتجات</h6>
        <small class="text-muted">{{ $rows->count() }} منتج</small>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>المنتج</th>
                    <th class="text-end">الكمية المباعة</th>
                    <th class="text-end">الإيرادات</th>
                    <th class="text-end">التكلفة</th>
                    <th class="text-end">الربح</th>
                    <th style="min-width:140px">هامش الربح%</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                @php
                    $margin = $row->margin;
                    $barColor = $margin >= 20 ? 'bg-success' : ($margin >= 10 ? 'bg-warning' : 'bg-danger');
                    $barWidth = min((float)$margin, 100);
                @endphp
                <tr>
                    <td><span class="badge bg-light text-dark font-monospace">{{ $row->product_code ?? '-' }}</span></td>
                    <td class="fw-semibold">{{ $row->product_name }}</td>
                    <td class="text-end">{{ number_format((float)$row->total_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$row->revenue, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$row->cost, 2) }}</td>
                    <td class="text-end {{ $row->profit >= 0 ? 'rpt-positive' : 'rpt-negative' }}">
                        {{ number_format((float)$row->profit, 2) }}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:8px">
                                <div class="progress-bar {{ $barColor }}" style="width:{{ $barWidth }}%"></div>
                            </div>
                            <small class="text-nowrap fw-semibold">{{ number_format($margin, 1) }}%</small>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="rpt-empty"><i class="fas fa-inbox d-block mb-2"></i>لا توجد بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3">الإجمالي</td>
                    <td class="text-end">{{ number_format($totals['revenue'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals['cost'], 2) }}</td>
                    <td class="text-end {{ $totals['profit'] >= 0 ? 'rpt-positive' : 'rpt-negative' }}">{{ number_format($totals['profit'], 2) }}</td>
                    <td>متوسط: {{ number_format($totals['margin'], 1) }}%</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</x-report-shell>
@endsection
