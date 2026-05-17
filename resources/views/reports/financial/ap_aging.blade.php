@extends('layouts.app')
@section('title', 'أعمار ديون الموردين')
@section('content')
<x-report-shell
    title="أعمار ديون الموردين"
    subtitle="تحليل المدفوعات المستحقة للموردين مصنّفة حسب مدة الاستحقاق"
    :export-route="route('reports.financial.ap-aging.export', request()->query())"
>

    {{-- ── KPI Cards ── --}}
    <x-slot:kpis>
        {{-- Total AP --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-truck"></i></div>
                <div class="rpt-stat-value rpt-negative">{{ number_format($totalAP, 2) }}</div>
                <div class="rpt-stat-label">إجمالي الذمم الدائنة ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- Current --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="rpt-stat-value">{{ number_format($buckets['current']->sum('balance_due'), 2) }}</div>
                <div class="rpt-stat-label">جارية — لم تحل بعد ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- 1-30 days --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="rpt-stat-value">{{ number_format($buckets['1_30']->sum('balance_due'), 2) }}</div>
                <div class="rpt-stat-label">متأخرة 1-30 يوم ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- 90+ days --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
                <div class="rpt-stat-value rpt-negative">{{ number_format($buckets['90_plus']->sum('balance_due'), 2) }}</div>
                <div class="rpt-stat-label">متأخرة أكثر من 90 يوم ({{ $company->currency }})</div>
            </div>
        </div>
    </x-slot:kpis>

    {{-- ── Chart ── --}}
    <x-slot:chart>
        <h6 class="fw-bold mb-3">توزيع الذمم الدائنة حسب الفئة الزمنية</h6>
        <canvas id="apAgingChart" height="80"></canvas>
    </x-slot:chart>

    {{-- ── Data Grid ── --}}
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">ملخص الأعمار</h6>
        <small class="text-muted">لحظة الآن — {{ now()->format('Y/m/d H:i') }}</small>
    </div>

    {{-- Section 1: Aging Summary Table --}}
    <div class="table-responsive border-bottom">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>الفترة</th>
                    <th class="text-center">عدد أوامر الشراء</th>
                    <th class="text-end">إجمالي المتبقي ({{ $company->currency }})</th>
                    <th class="text-end">% من الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $agingRows = [
                        ['label' => 'جارية (لم تحل بعد)', 'key' => 'current',  'color' => 'success'],
                        ['label' => '1-30 يوم',            'key' => '1_30',     'color' => 'warning'],
                        ['label' => '31-60 يوم',           'key' => '31_60',    'color' => 'orange'],
                        ['label' => '61-90 يوم',           'key' => '61_90',    'color' => 'danger'],
                        ['label' => 'أكثر من 90 يوم',     'key' => '90_plus',  'color' => 'danger'],
                    ];
                @endphp
                @foreach($agingRows as $row)
                @php
                    $bucket = $buckets[$row['key']];
                    $bucketTotal = $bucket->sum('balance_due');
                    $pct = $totalAP > 0 ? round(($bucketTotal / $totalAP) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>
                        <span class="badge bg-{{ $row['color'] }}-subtle text-{{ $row['color'] }}">
                            {{ $row['label'] }}
                        </span>
                    </td>
                    <td class="text-center">{{ $bucket->count() }}</td>
                    <td class="text-end fw-semibold {{ $bucketTotal > 0 ? 'rpt-negative' : 'text-muted' }}">
                        {{ number_format($bucketTotal, 2) }}
                    </td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <div class="progress flex-grow-1" style="height:6px;min-width:80px;">
                                <div class="progress-bar bg-{{ $row['color'] }}" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="text-muted small">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                <tr class="fw-bold bg-light">
                    <td>الإجمالي</td>
                    <td class="text-center">
                        {{ collect($buckets)->sum(fn($b) => $b->count()) }}
                    </td>
                    <td class="text-end rpt-negative">{{ number_format($totalAP, 2) }}</td>
                    <td class="text-end text-muted">100%</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Section 2: Top Suppliers --}}
    <div class="card-header bg-white border-top">
        <h6 class="mb-0 fw-bold">أعلى الموردين من حيث المستحق</h6>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المورد</th>
                    <th>الهاتف</th>
                    <th class="text-center">عدد أوامر الشراء</th>
                    <th>أقدم أمر</th>
                    <th class="text-end">إجمالي المتبقي ({{ $company->currency }})</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bySupplier as $i => $row)
                <tr>
                    <td class="text-muted small">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $row['supplier']?->name ?? 'غير محدد' }}</td>
                    <td class="text-muted">{{ $row['supplier']?->phone ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary">{{ $row['count'] }}</span>
                    </td>
                    <td class="text-muted small">{{ $row['oldest_due'] ?? '-' }}</td>
                    <td class="text-end rpt-negative fw-bold">{{ number_format($row['total'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="rpt-empty">
                        <i class="fas fa-check-circle d-block mb-2 text-success" style="opacity:1;"></i>
                        لا توجد ديون مستحقة للموردين — رائع!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-report-shell>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('apAgingChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['جارية', '1-30 يوم', '31-60 يوم', '61-90 يوم', 'أكثر من 90 يوم'],
            datasets: [{
                label: 'المتبقي ({{ $company->currency }})',
                data: [
                    {{ $buckets['current']->sum('balance_due') }},
                    {{ $buckets['1_30']->sum('balance_due') }},
                    {{ $buckets['31_60']->sum('balance_due') }},
                    {{ $buckets['61_90']->sum('balance_due') }},
                    {{ $buckets['90_plus']->sum('balance_due') }},
                ],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(234, 88, 12, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(185, 28, 28, 0.7)',
                ],
                borderColor: [
                    '#10b981', '#f59e0b', '#ea580c', '#ef4444', '#b91c1c',
                ],
                borderWidth: 1,
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return Number(ctx.raw).toLocaleString('ar-SA', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { callback: function(v) { return Number(v).toLocaleString(); } }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
