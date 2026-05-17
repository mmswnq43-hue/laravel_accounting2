@extends('layouts.app')
@section('title', 'الإقرار الضريبي - ضريبة القيمة المضافة')
@section('content')
<x-report-shell
    title="الإقرار الضريبي - ضريبة القيمة المضافة"
    subtitle="ملخص ضريبة المخرجات والمدخلات وفق متطلبات هيئة الزكاة والضريبة والجمارك (زاتكا)"
    :export-route="route('reports.tax.vat.export', request()->query())"
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
                <a href="{{ route('reports.tax.vat') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </x-slot:filters>

    <x-slot:kpis>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon blue"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="rpt-stat-value">{{ number_format($outputVat, 2) }}</div>
                <div class="rpt-stat-label">ضريبة المخرجات (المبيعات) {{ $company->currency }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon orange"><i class="fas fa-file-import"></i></div>
                <div class="rpt-stat-value">{{ number_format($inputVat, 2) }}</div>
                <div class="rpt-stat-label">ضريبة المدخلات (المشتريات) {{ $company->currency }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon {{ $netVat >= 0 ? 'red' : 'green' }}"><i class="fas fa-balance-scale"></i></div>
                <div class="rpt-stat-value {{ $netVat >= 0 ? 'rpt-negative' : 'rpt-positive' }}">{{ number_format(abs($netVat), 2) }}</div>
                <div class="rpt-stat-label">{{ $netVat >= 0 ? 'ضريبة مستحقة الدفع' : 'ضريبة مستردة' }} {{ $company->currency }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rpt-stat-card">
                <div class="rpt-stat-icon purple"><i class="fas fa-receipt"></i></div>
                <div class="rpt-stat-value">{{ number_format(($salesSummary->invoice_count ?? 0) + ($purchasesSummary->purchase_count ?? 0)) }}</div>
                <div class="rpt-stat-label">إجمالي المعاملات الخاضعة</div>
            </div>
        </div>
    </x-slot:kpis>

    {{-- Main VAT Return Form --}}
    <style>
        .vat-section-header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white;
            padding: 0.75rem 1.25rem;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 8px 8px 0 0;
        }
        .vat-table th { background: #f0f7ff; font-weight: 700; color: #1e40af; }
        .vat-total-row { background: #f8faff; font-weight: 700; border-top: 2px solid #2563eb; }
        .vat-net-row { background: #fffbeb; font-weight: 800; border-top: 3px solid #f59e0b; font-size: 1.05rem; }
        .vat-net-row.payable { background: #fff1f2; border-color: #ef4444; }
        .vat-net-row.refund { background: #f0fdf4; border-color: #10b981; }
        .box-badge { display: inline-block; background: #2563eb; color: white; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 4px; margin-left: 0.5rem; }
    </style>

    <div class="p-4">

        {{-- Company info header --}}
        <div class="row mb-4 p-3 border rounded bg-light">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="fw-bold fs-5">{{ $company->name }}</div>
                        @if(isset($company->tax_number) && $company->tax_number)
                        <div class="text-muted small">الرقم الضريبي: <span class="fw-bold text-dark">{{ $company->tax_number }}</span></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="text-muted small">الفترة الضريبية</div>
                <div class="fw-bold">{{ $from->format('Y/m/d') }} — {{ $to->format('Y/m/d') }}</div>
                <div class="text-muted small mt-1">تاريخ الطباعة: {{ now()->format('Y/m/d') }}</div>
            </div>
        </div>

        {{-- Section A: Output VAT --}}
        <div class="mb-4">
            <div class="vat-section-header">
                <i class="fas fa-arrow-up me-2"></i> القسم الأول: ضريبة المخرجات (المبيعات)
            </div>
            <div class="table-responsive border border-top-0 rounded-bottom">
                <table class="table rpt-table mb-0 vat-table">
                    <thead>
                        <tr>
                            <th>البيان <span class="box-badge">ZATCA</span></th>
                            <th class="text-end">الأساس الخاضع للضريبة ({{ $company->currency }})</th>
                            <th class="text-end">مبلغ الضريبة ({{ $company->currency }})</th>
                            <th class="text-end">عدد المعاملات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outputByRate as $row)
                        <tr>
                            <td>مبيعات خاضعة للضريبة بنسبة <strong>{{ number_format($row->tax_rate, 2) }}%</strong> <span class="box-badge">Box 1a</span></td>
                            <td class="text-end">{{ number_format($row->base_amount, 2) }}</td>
                            <td class="text-end rpt-positive fw-bold">{{ number_format($row->vat_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($row->transaction_count) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="rpt-empty"><i class="fas fa-inbox d-block mb-1"></i>لا توجد مبيعات خاضعة في هذه الفترة</td></tr>
                        @endforelse
                        @if($zeroRatedSales && ($zeroRatedSales->base_amount ?? 0) > 0)
                        <tr>
                            <td>مبيعات معفاة / بنسبة صفرية <span class="box-badge">Box 1b</span></td>
                            <td class="text-end">{{ number_format($zeroRatedSales->base_amount, 2) }}</td>
                            <td class="text-end text-muted">0.00</td>
                            <td class="text-end">{{ number_format($zeroRatedSales->transaction_count) }}</td>
                        </tr>
                        @endif
                        <tr class="vat-total-row">
                            <td>إجمالي المبيعات <span class="box-badge">Box 1</span></td>
                            <td class="text-end">{{ number_format($salesSummary->taxable_sales ?? 0, 2) }}</td>
                            <td class="text-end rpt-positive">{{ number_format($outputVat, 2) }}</td>
                            <td class="text-end">{{ number_format($salesSummary->invoice_count ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section B: Input VAT --}}
        <div class="mb-4">
            <div class="vat-section-header" style="background: linear-gradient(135deg, #065f46, #10b981);">
                <i class="fas fa-arrow-down me-2"></i> القسم الثاني: ضريبة المدخلات (المشتريات)
            </div>
            <div class="table-responsive border border-top-0 rounded-bottom">
                <table class="table rpt-table mb-0 vat-table">
                    <thead>
                        <tr>
                            <th>البيان <span class="box-badge" style="background:#065f46">ZATCA</span></th>
                            <th class="text-end">الأساس الخاضع للضريبة ({{ $company->currency }})</th>
                            <th class="text-end">مبلغ الضريبة ({{ $company->currency }})</th>
                            <th class="text-end">عدد المعاملات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inputByRate as $row)
                        <tr>
                            <td>مشتريات خاضعة للضريبة بنسبة <strong>{{ number_format($row->tax_rate, 2) }}%</strong> <span class="box-badge" style="background:#065f46">Box 6a</span></td>
                            <td class="text-end">{{ number_format($row->base_amount, 2) }}</td>
                            <td class="text-end rpt-negative fw-bold">{{ number_format($row->vat_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($row->transaction_count) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="rpt-empty"><i class="fas fa-inbox d-block mb-1"></i>لا توجد مشتريات خاضعة في هذه الفترة</td></tr>
                        @endforelse
                        <tr class="vat-total-row">
                            <td>إجمالي المشتريات <span class="box-badge" style="background:#065f46">Box 6</span></td>
                            <td class="text-end">{{ number_format($purchasesSummary->taxable_purchases ?? 0, 2) }}</td>
                            <td class="text-end rpt-negative">{{ number_format($inputVat, 2) }}</td>
                            <td class="text-end">{{ number_format($purchasesSummary->purchase_count ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section C: Net VAT --}}
        <div class="mb-4">
            <div class="vat-section-header" style="background: linear-gradient(135deg, #7c3aed, #9333ea);">
                <i class="fas fa-calculator me-2"></i> القسم الثالث: صافي الضريبة المستحقة
            </div>
            <div class="border border-top-0 rounded-bottom">
                <table class="table rpt-table mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold">إجمالي ضريبة المخرجات <span class="box-badge" style="background:#7c3aed">Box 1</span></td>
                            <td class="text-end fw-bold">{{ number_format($outputVat, 2) }} {{ $company->currency }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">إجمالي ضريبة المدخلات القابلة للخصم <span class="box-badge" style="background:#7c3aed">Box 6</span></td>
                            <td class="text-end fw-bold">({{ number_format($inputVat, 2) }}) {{ $company->currency }}</td>
                        </tr>
                        <tr class="vat-net-row {{ $netVat >= 0 ? 'payable' : 'refund' }}">
                            <td>
                                @if($netVat >= 0)
                                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                    <strong>الضريبة المستحقة الدفع لزاتكا</strong> <span class="box-badge" style="background:#ef4444">Box 9</span>
                                @else
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>ضريبة مستردة من زاتكا</strong> <span class="box-badge" style="background:#10b981">Box 9</span>
                                @endif
                            </td>
                            <td class="text-end {{ $netVat >= 0 ? 'rpt-negative' : 'rpt-positive' }}" style="font-size:1.2rem;">
                                {{ number_format(abs($netVat), 2) }} {{ $company->currency }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Disclaimer --}}
        <div class="alert alert-info border-0 small">
            <i class="fas fa-info-circle me-2"></i>
            هذا التقرير مُعدّ للمراجعة الداخلية. قبل تقديم الإقرار الضريبي على بوابة زاتكا، تأكد من مراجعة جميع الأرقام مع المحاسب القانوني المعتمد.
        </div>

    </div>
</x-report-shell>
@endsection
