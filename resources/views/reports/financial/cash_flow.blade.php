@extends('layouts.app')
@section('title', 'التدفقات النقدية')
@section('content')
<x-report-shell
    title="التدفقات النقدية"
    subtitle="تتبع الحركات النقدية الفعلية خلال الفترة المحددة"
    :export-route="route('reports.financial.cash-flow.export', request()->query())"
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
                <a href="{{ route('reports.financial.cash-flow') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </x-slot:filters>

    {{-- ── KPI Cards ── --}}
    <x-slot:kpis>
        {{-- Operating Inflows --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-arrow-down"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['operatingIn'], 2) }}</div>
                <div class="rpt-stat-label">التحصيلات التشغيلية ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- Operating Outflows --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-arrow-up"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary['operatingOut'] + $summary['expenses'], 2) }}</div>
                <div class="rpt-stat-label">المدفوعات التشغيلية ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- Net Operating --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon {{ $summary['netOperating'] >= 0 ? 'blue' : 'red' }}">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="rpt-stat-value {{ $summary['netOperating'] >= 0 ? 'rpt-positive' : 'rpt-negative' }}">
                    {{ number_format($summary['netOperating'], 2) }}
                </div>
                <div class="rpt-stat-label">صافي الأنشطة التشغيلية ({{ $company->currency }})</div>
            </div>
        </div>
        {{-- Net Cash --}}
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon {{ $summary['netCash'] >= 0 ? 'blue' : 'red' }}">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="rpt-stat-value {{ $summary['netCash'] >= 0 ? 'rpt-positive' : 'rpt-negative' }}">
                    {{ number_format($summary['netCash'], 2) }}
                </div>
                <div class="rpt-stat-label">صافي التغيير النقدي ({{ $company->currency }})</div>
            </div>
        </div>
    </x-slot:kpis>

    {{-- ── Chart ── --}}
    <x-slot:chart>
        <h6 class="fw-bold mb-3">التدفق النقدي الشهري</h6>
        <canvas id="cashFlowChart" height="100"></canvas>
    </x-slot:chart>

    {{-- ── Data Grid ── --}}
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">تفاصيل التدفقات النقدية</h6>
        <small class="text-muted">{{ $from->format('Y/m/d') }} — {{ $to->format('Y/m/d') }}</small>
    </div>

    <div class="row g-0">
        {{-- Left Panel: Operating Activities Summary --}}
        <div class="col-md-5 border-end">
            <div class="p-4">
                <h6 class="fw-bold mb-4 text-primary">
                    <i class="fas fa-cogs me-2"></i>تدفقات الأنشطة التشغيلية
                </h6>
                <table class="table rpt-table mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted">تحصيل من العملاء</td>
                            <td class="text-end rpt-positive fw-semibold">
                                {{ number_format($summary['operatingIn'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">مدفوعات للموردين</td>
                            <td class="text-end rpt-negative fw-semibold">
                                ({{ number_format($summary['operatingOut'], 2) }})
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">مصروفات تشغيلية</td>
                            <td class="text-end rpt-negative fw-semibold">
                                ({{ number_format($summary['expenses'], 2) }})
                            </td>
                        </tr>
                        <tr class="fw-bold" style="border-top: 2px solid #dce5ee;">
                            <td>صافي الأنشطة التشغيلية</td>
                            <td class="text-end {{ $summary['netOperating'] >= 0 ? 'rpt-positive' : 'rpt-negative' }}">
                                {{ number_format($summary['netOperating'], 2) }}
                            </td>
                        </tr>
                        <tr class="fw-bold bg-light">
                            <td>صافي التغيير النقدي الكلي</td>
                            <td class="text-end {{ $summary['netCash'] >= 0 ? 'rpt-positive' : 'rpt-negative' }} fs-5">
                                {{ number_format($summary['netCash'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-muted small mt-3 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    الأنشطة الاستثمارية والتمويلية غير مدرجة حتى الآن.
                </p>
            </div>
        </div>

        {{-- Right Panel: Recent Payments --}}
        <div class="col-md-7">
            <div class="p-3">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-list me-2"></i>آخر 10 حركات نقدية
                </h6>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الفئة</th>
                                <th>الاتجاه</th>
                                <th class="text-end">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments->sortByDesc('payment_date')->take(10) as $payment)
                            <tr>
                                <td>{{ $payment->payment_date }}</td>
                                <td>
                                    @php
                                        $catMap = [
                                            'invoice_receipt'    => ['success', 'تحصيل فاتورة'],
                                            'purchase_payment'   => ['danger',  'دفع مشتريات'],
                                            'supplier_payment'   => ['danger',  'دفع مورد'],
                                        ];
                                        [$cls, $lbl] = $catMap[$payment->payment_category] ?? ['secondary', $payment->payment_category];
                                    @endphp
                                    <span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $lbl }}</span>
                                </td>
                                <td>
                                    @if($payment->payment_direction === 'in')
                                        <span class="badge rpt-badge-success"><i class="fas fa-arrow-down me-1"></i>داخل</span>
                                    @else
                                        <span class="badge rpt-badge-danger"><i class="fas fa-arrow-up me-1"></i>خارج</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $payment->payment_direction === 'in' ? 'rpt-positive' : 'rpt-negative' }}">
                                    {{ number_format($payment->amount, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="rpt-empty">
                                    <i class="fas fa-inbox d-block mb-2"></i>
                                    لا توجد حركات نقدية في هذه الفترة
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</x-report-shell>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('cashFlowChart');
    if (!ctx) return;

    const labels = @json(collect($monthly)->pluck('label'));
    const inData = @json(collect($monthly)->pluck('in'));
    const outData = @json(collect($monthly)->pluck('out'));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'داخل',
                    data: inData,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'خارج',
                    data: outData,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString('ar-SA', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return Number(value).toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
