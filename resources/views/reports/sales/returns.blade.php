@extends('layouts.app')
@section('title', 'مرتجع وملغي المبيعات')
@section('content')
@php
    $avgReturn = ($summary->total_count ?? 0) > 0
        ? ($summary->total_value / $summary->total_count)
        : 0;
@endphp
<x-report-shell
    title="مرتجع وملغي المبيعات"
    subtitle="سجل الفواتير الملغاة والمرتجعة والمستردة في الفترة المحددة"
    :export-route="route('reports.sales.returns.export', request()->query())"
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
                <a href="{{ route('reports.sales.returns') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-4">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-file-invoice"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary->total_count ?? 0) }}</div>
                <div class="rpt-stat-label">عدد الفواتير الملغاة / المرتجعة</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-undo-alt"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary->total_value ?? 0, 2) }}</div>
                <div class="rpt-stat-label">إجمالي القيمة ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-calculator"></i></div>
                <div class="rpt-stat-value">{{ number_format($avgReturn, 2) }}</div>
                <div class="rpt-stat-label">متوسط قيمة المرتجع ({{ $company->currency }})</div>
            </div>
        </div>
    </x-slot:kpis>

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">تفاصيل المرتجعات والملغيات</h6>
        <small class="text-muted">{{ $invoices->total() }} فاتورة</small>
    </div>
    <div class="table-responsive">
        <table class="table rpt-table mb-0">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>التاريخ</th>
                    <th>العميل</th>
                    <th>القناة</th>
                    <th class="text-end">الإجمالي</th>
                    <th>الحالة</th>
                    <th>الملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                @php
                    $statusMap = [
                        'cancelled' => ['warning', 'ملغاة'],
                        'returned'  => ['danger',  'مرتجعة'],
                        'refunded'  => ['info',    'مستردة'],
                    ];
                    [$cls, $lbl] = $statusMap[$invoice->status] ?? ['secondary', $invoice->status];
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none fw-semibold">
                            {{ $invoice->invoice_number }}
                        </a>
                    </td>
                    <td>{{ $invoice->invoice_date?->format('Y/m/d') }}</td>
                    <td>{{ $invoice->customer?->name ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $invoice->salesChannel?->name ?? '-' }}</span></td>
                    <td class="text-end rpt-negative fw-bold">{{ number_format($invoice->total, 2) }}</td>
                    <td><span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $lbl }}</span></td>
                    <td class="text-muted small">{{ Str::limit($invoice->notes, 60) ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="rpt-empty"><i class="fas fa-check-circle d-block mb-2 text-success"></i>لا توجد مرتجعات أو ملغيات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="p-3">{{ $invoices->links() }}</div>
    @endif
</x-report-shell>
@endsection
