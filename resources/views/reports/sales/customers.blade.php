@extends('layouts.app')
@section('title', 'تحليل مبيعات العملاء')
@section('content')
@php
    $avgPerCustomer = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
    $topCustomer    = $customers->first();
@endphp
<x-report-shell
    title="تحليل مبيعات العملاء"
    subtitle="ترتيب العملاء حسب حجم المشتريات وتحليل سلوك الدفع في الفترة المحددة"
    :export-route="route('reports.sales.customers.export', request()->query())"
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
                <a href="{{ route('reports.sales.customers') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="rpt-stat-value">{{ number_format($totalCustomers) }}</div>
                <div class="rpt-stat-label">إجمالي العملاء</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="rpt-stat-value">{{ number_format($totalRevenue, 2) }}</div>
                <div class="rpt-stat-label">إجمالي الإيرادات ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-chart-bar"></i></div>
                <div class="rpt-stat-value">{{ number_format($avgPerCustomer, 2) }}</div>
                <div class="rpt-stat-label">متوسط المشتريات لكل عميل</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon purple"><i class="fas fa-trophy"></i></div>
                <div class="rpt-stat-value">{{ $topCustomer?->name ?? '-' }}</div>
                <div class="rpt-stat-label">أعلى عميل مشتريات</div>
            </div>
        </div>
    </x-slot:kpis>

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">تصنيف العملاء حسب حجم المشتريات</h6>
        <small class="text-muted">{{ $customers->total() }} عميل</small>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th style="width:50px">الرتبة</th>
                    <th>العميل</th>
                    <th>الهاتف</th>
                    <th class="text-end">عدد الفواتير</th>
                    <th class="text-end">إجمالي المشتريات</th>
                    <th class="text-end">المدفوع</th>
                    <th class="text-end">المتبقي</th>
                    <th class="text-end">% من الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $index => $customer)
                @php
                    $rank = $customers->firstItem() + $index;
                    $pct  = $totalRevenue > 0
                        ? round(($customer->total_purchase / $totalRevenue) * 100, 2)
                        : 0;
                @endphp
                <tr>
                    <td>
                        @if($rank <= 3)
                            <span class="badge bg-{{ ['warning','secondary','danger'][$rank-1] ?? 'light' }} text-{{ $rank == 1 ? 'dark' : 'white' }}">
                                {{ $rank }}
                            </span>
                        @else
                            <span class="text-muted">{{ $rank }}</span>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $customer->name }}</td>
                    <td>{{ $customer->phone ?? '-' }}</td>
                    <td class="text-end">{{ number_format($customer->invoice_count) }}</td>
                    <td class="text-end fw-bold">{{ number_format((float)$customer->total_purchase, 2) }}</td>
                    <td class="text-end rpt-positive">{{ number_format((float)$customer->total_paid, 2) }}</td>
                    <td class="text-end {{ $customer->total_outstanding > 0 ? 'rpt-negative' : '' }}">
                        {{ number_format((float)$customer->total_outstanding, 2) }}
                    </td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <div class="progress flex-grow-1" style="height:6px; max-width:60px;">
                                <div class="progress-bar bg-primary" style="width:{{ min($pct, 100) }}%"></div>
                            </div>
                            <small class="fw-semibold">{{ $pct }}%</small>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="rpt-empty"><i class="fas fa-inbox d-block mb-2"></i>لا توجد بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="p-3">{{ $customers->links() }}</div>
    @endif
</x-report-shell>
@endsection
