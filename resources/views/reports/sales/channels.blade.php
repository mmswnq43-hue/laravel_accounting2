@extends('layouts.app')
@section('title', 'المبيعات حسب القنوات والفروع')
@section('content')
@php
    $topChannel = $byChannel->first();
    $topBranch  = $byBranch->first();
@endphp
<x-report-shell
    title="المبيعات حسب القنوات والفروع"
    subtitle="توزيع الإيرادات على قنوات البيع والفروع خلال الفترة المحددة"
    :export-route="route('reports.sales.channels.export', request()->query())"
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
                <a href="{{ route('reports.sales.channels') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-coins"></i></div>
                <div class="rpt-stat-value">{{ number_format((float)($grandTotal->revenue ?? 0), 2) }}</div>
                <div class="rpt-stat-label">إجمالي الإيرادات ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-broadcast-tower"></i></div>
                <div class="rpt-stat-value">{{ $topChannel?->name ?? '-' }}</div>
                <div class="rpt-stat-label">أفضل قناة مبيعات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-file-invoice"></i></div>
                <div class="rpt-stat-value">{{ number_format($grandTotal->count ?? 0) }}</div>
                <div class="rpt-stat-label">إجمالي الفواتير</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon purple"><i class="fas fa-code-branch"></i></div>
                <div class="rpt-stat-value">{{ $topBranch?->name ?? '-' }}</div>
                <div class="rpt-stat-label">أفضل فرع</div>
            </div>
        </div>
    </x-slot:kpis>

    <x-slot:chart>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title fw-bold mb-3">توزيع الإيرادات حسب القناة</h6>
                <div style="max-height:280px; display:flex; justify-content:center;">
                    <canvas id="channelDoughnut" style="max-width:280px;"></canvas>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('channelDoughnut').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($byChannel->pluck('name')->toArray()) !!},
                    datasets: [{
                        data: {!! json_encode($byChannel->pluck('total_revenue')->map(fn($v) => round((float)$v, 2))->toArray()) !!},
                        backgroundColor: [
                            'rgba(59,130,246,0.8)',
                            'rgba(16,185,129,0.8)',
                            'rgba(245,158,11,0.8)',
                            'rgba(139,92,246,0.8)',
                            'rgba(239,68,68,0.8)',
                            'rgba(6,182,212,0.8)',
                        ],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
        </script>
    </x-slot:chart>

    <div class="row g-3">
        {{-- By Channel --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-broadcast-tower text-primary me-2"></i>المبيعات حسب القناة
                </div>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>القناة</th>
                                <th class="text-end">عدد الفواتير</th>
                                <th class="text-end">الإيرادات</th>
                                <th class="text-end">متوسط الفاتورة</th>
                                <th class="text-end">النسبة%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byChannel as $ch)
                            @php
                                $pct = ($grandTotal->revenue ?? 0) > 0
                                    ? round(($ch->total_revenue / $grandTotal->revenue) * 100, 1)
                                    : 0;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $ch->name }}</td>
                                <td class="text-end">{{ number_format($ch->invoice_count) }}</td>
                                <td class="text-end">{{ number_format((float)$ch->total_revenue, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$ch->avg_invoice, 2) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-primary-subtle text-primary">{{ $pct }}%</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="rpt-empty">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- By Branch --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="fas fa-code-branch text-purple me-2"></i>المبيعات حسب الفرع
                </div>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>الفرع</th>
                                <th class="text-end">عدد الفواتير</th>
                                <th class="text-end">الإيرادات</th>
                                <th class="text-end">متوسط الفاتورة</th>
                                <th class="text-end">النسبة%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byBranch as $br)
                            @php
                                $pct = ($grandTotal->revenue ?? 0) > 0
                                    ? round(($br->total_revenue / $grandTotal->revenue) * 100, 1)
                                    : 0;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $br->name }}</td>
                                <td class="text-end">{{ number_format($br->invoice_count) }}</td>
                                <td class="text-end">{{ number_format((float)$br->total_revenue, 2) }}</td>
                                <td class="text-end">{{ number_format((float)$br->avg_invoice, 2) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-purple-subtle text-purple">{{ $pct }}%</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="rpt-empty">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-report-shell>
@endsection
