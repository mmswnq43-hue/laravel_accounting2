@extends('layouts.app')
@section('title', 'تحليل وتفكيك المصروفات')
@section('content')
<x-report-shell
    title="تحليل وتفكيك المصروفات"
    subtitle="عرض تفصيلي للمصروفات مصنّفة حسب الحساب والفترة الزمنية"
    :export-route="route('reports.financial.expenses.export', request()->query())"
>

    {{-- ── Filters ── --}}
    <x-slot:filters>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">من تاريخ</label>
                <input type="date" name="from_date" class="form-control form-control-sm"
                       value="{{ request('from_date', $from->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control form-control-sm"
                       value="{{ request('to_date', $to->format('Y-m-d')) }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>تطبيق
                </button>
                <a href="{{ route('reports.financial.expenses') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </x-slot:filters>

    {{-- ── KPI Cards ── --}}
    <x-slot:kpis>
        {{-- Total --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-receipt"></i></div>
                <div class="rpt-stat-value rpt-negative">{{ number_format($summary['total'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي المصروفات ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- Count --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-list-ol"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['count']) }}</div>
                <div class="rpt-stat-label">عدد المعاملات</div>
            </div>
        </div>
        {{-- Average --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-calculator"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['avg'], 2) }}</div>
                <div class="rpt-stat-label">متوسط المصروف ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- Categories --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon purple"><i class="fas fa-tags"></i></div>
                <div class="rpt-stat-value">{{ $summary['categories'] }}</div>
                <div class="rpt-stat-label">عدد الفئات / الحسابات</div>
            </div>
        </div>
    </x-slot:kpis>

    {{-- ── Charts ── --}}
    <x-slot:chart>
        <div class="row g-3">
            <div class="col-md-5">
                <h6 class="fw-bold mb-3">توزيع المصروفات حسب الحساب</h6>
                <canvas id="expenseDoughnutChart" height="220"></canvas>
            </div>
            <div class="col-md-7">
                <h6 class="fw-bold mb-3">الاتجاه الشهري للمصروفات</h6>
                <canvas id="expenseLineChart" height="220"></canvas>
            </div>
        </div>
    </x-slot:chart>

    {{-- ── Data Grid ── --}}

    {{-- Section 1: By Account Summary --}}
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">المصروفات حسب الحساب</h6>
        <small class="text-muted">{{ $from->format('Y/m/d') }} — {{ $to->format('Y/m/d') }}</small>
    </div>
    <div class="table-responsive border-bottom">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>كود الحساب</th>
                    <th>الحساب</th>
                    <th class="text-center">عدد المعاملات</th>
                    <th class="text-end">الضريبة ({{ $company->currency }})</th>
                    <th class="text-end">الإجمالي ({{ $company->currency }})</th>
                    <th class="text-end">% من الكل</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byAccount as $row)
                @php $pct = $summary['total'] > 0 ? round(($row['total'] / $summary['total']) * 100, 1) : 0; @endphp
                <tr>
                    <td><code class="text-muted">{{ $row['account_code'] }}</code></td>
                    <td class="fw-semibold">{{ $row['account_name'] }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary">{{ $row['count'] }}</span>
                    </td>
                    <td class="text-end text-muted">{{ number_format($row['tax'], 2) }}</td>
                    <td class="text-end fw-bold rpt-negative">{{ number_format($row['total'], 2) }}</td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <div class="progress flex-grow-1" style="height:6px;min-width:80px;">
                                <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="text-muted small">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="rpt-empty">
                        <i class="fas fa-inbox d-block mb-2"></i>
                        لا توجد مصروفات في هذه الفترة
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($byAccount->isNotEmpty())
            <tfoot>
                <tr class="fw-bold bg-light">
                    <td colspan="2">الإجمالي</td>
                    <td class="text-center">{{ $summary['count'] }}</td>
                    <td class="text-end">{{ number_format($expenses->sum('tax_amount'), 2) }}</td>
                    <td class="text-end rpt-negative">{{ number_format($summary['total'], 2) }}</td>
                    <td class="text-end text-muted">100%</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- Section 2: Detailed Expense List (last 25) --}}
    <div class="card-header bg-white border-top d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">آخر المصروفات التفصيلية</h6>
        <small class="text-muted">عرض آخر 25 مصروف</small>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الاسم</th>
                    <th>الحساب</th>
                    <th class="text-end">المبلغ</th>
                    <th class="text-end">الضريبة</th>
                    <th class="text-end">الإجمالي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses->take(25) as $expense)
                <tr>
                    <td class="text-muted small">{{ $expense->expense_date->format('Y/m/d') }}</td>
                    <td class="fw-semibold">{{ $expense->name }}</td>
                    <td>
                        <span class="badge bg-light text-dark">
                            {{ $expense->expenseAccount?->name_ar ?? ($expense->expenseAccount?->name ?? 'غير محدد') }}
                        </span>
                    </td>
                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                    <td class="text-end text-muted">{{ number_format($expense->tax_amount, 2) }}</td>
                    <td class="text-end fw-bold rpt-negative">{{ number_format($expense->total, 2) }}</td>
                    <td>
                        @php
                            $statusMap = [
                                'paid'    => ['success', 'مدفوع'],
                                'pending' => ['warning', 'معلق'],
                                'draft'   => ['secondary', 'مسودة'],
                            ];
                            [$cls, $lbl] = $statusMap[$expense->status] ?? ['secondary', $expense->status];
                        @endphp
                        <span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $lbl }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="rpt-empty">
                        <i class="fas fa-inbox d-block mb-2"></i>
                        لا توجد مصروفات في هذه الفترة
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($expenses->count() > 25)
    <div class="p-3 text-center text-muted small border-top">
        <i class="fas fa-info-circle me-1"></i>
        يُعرض آخر 25 مصروف — صدِّر Excel للاطلاع على البيانات الكاملة ({{ $expenses->count() }} سجل)
    </div>
    @endif

</x-report-shell>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Doughnut chart: by account ──────────────────────────────
    const doughnutCtx = document.getElementById('expenseDoughnutChart');
    if (doughnutCtx) {
        const accountLabels = @json($byAccount->pluck('account_name'));
        const accountTotals = @json($byAccount->pluck('total'));
        const palette = [
            '#ef4444','#f59e0b','#10b981','#3b82f6','#8b5cf6',
            '#ec4899','#06b6d4','#84cc16','#f97316','#6366f1',
        ];

        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: accountLabels,
                datasets: [{
                    data: accountTotals,
                    backgroundColor: palette.slice(0, accountLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 11 }, padding: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.label + ': ' + Number(ctx.raw).toLocaleString('ar-SA', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });
    }

    // ── Line chart: monthly trend ──────────────────────────────
    const lineCtx = document.getElementById('expenseLineChart');
    if (lineCtx) {
        const monthlyLabels = @json($monthly->pluck('label'));
        const monthlyTotals = @json($monthly->pluck('total'));

        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'إجمالي المصروفات',
                    data: monthlyTotals,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#ef4444',
                    pointRadius: 4,
                }],
            },
            options: {
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
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return Number(v).toLocaleString(); } }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
