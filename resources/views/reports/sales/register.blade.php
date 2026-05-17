@extends('layouts.app')
@section('title', 'سجل المبيعات التفصيلي')
@section('content')
<x-report-shell
    title="سجل المبيعات التفصيلي"
    subtitle="عرض تسلسلي لجميع الفواتير والمبيعات في الفترة المحددة"
    :export-route="route('reports.sales.register.export', request()->query())"
>
    <x-slot:filters>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">من تاريخ</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', $from->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', $to->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">العميل</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">كل العملاء</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">القناة</label>
                <select name="sales_channel_id" class="form-select form-select-sm">
                    <option value="">كل القنوات</option>
                    @foreach($channels as $ch)
                        <option value="{{ $ch->id }}" {{ request('sales_channel_id') == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">كل الحالات</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>مدفوعة</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>مرسلة</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>متأخرة</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>تطبيق</button>
                <a href="{{ route('reports.sales.register') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-file-invoice"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary->total_count ?? 0) }}</div>
                <div class="rpt-stat-label">إجمالي الفواتير</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary->total_revenue ?? 0, 2) }}</div>
                <div class="rpt-stat-label">إجمالي المبيعات ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary->total_collected ?? 0, 2) }}</div>
                <div class="rpt-stat-label">المحصّل ({{ $company->currency }})</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
                <div class="rpt-stat-value">{{ number_format($summary->total_outstanding ?? 0, 2) }}</div>
                <div class="rpt-stat-label">المتبقي ({{ $company->currency }})</div>
            </div>
        </div>
    </x-slot:kpis>

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">تفاصيل الفواتير</h6>
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
                    <th>الفرع</th>
                    <th class="text-end">الإجمالي قبل الضريبة</th>
                    <th class="text-end">الضريبة</th>
                    <th class="text-end">الإجمالي</th>
                    <th class="text-end">المدفوع</th>
                    <th class="text-end">المتبقي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none fw-semibold">{{ $invoice->invoice_number }}</a></td>
                    <td>{{ $invoice->invoice_date?->format('Y/m/d') }}</td>
                    <td>{{ $invoice->customer?->name ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $invoice->salesChannel?->name ?? '-' }}</span></td>
                    <td>{{ $invoice->branch?->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td>
                    <td class="text-end">{{ number_format($invoice->tax_amount, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($invoice->total, 2) }}</td>
                    <td class="text-end rpt-positive">{{ number_format($invoice->paid_amount, 2) }}</td>
                    <td class="text-end {{ $invoice->balance_due > 0 ? 'rpt-negative' : 'rpt-muted-val' }}">{{ number_format($invoice->balance_due, 2) }}</td>
                    <td>
                        @php
                            $statusMap = ['paid'=>['success','مدفوعة'],'sent'=>['primary','مرسلة'],'draft'=>['secondary','مسودة'],'overdue'=>['danger','متأخرة'],'cancelled'=>['warning','ملغاة']];
                            [$cls,$lbl] = $statusMap[$invoice->status] ?? ['secondary', $invoice->status];
                        @endphp
                        <span class="badge bg-{{ $cls }}-subtle text-{{ $cls }}">{{ $lbl }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="rpt-empty"><i class="fas fa-inbox d-block mb-2"></i>لا توجد فواتير في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="p-3">{{ $invoices->links() }}</div>
    @endif
</x-report-shell>
@endsection
