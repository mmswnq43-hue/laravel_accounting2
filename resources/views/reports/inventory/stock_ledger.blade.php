@extends('layouts.app')
@section('title', 'حركة كرت الصنف')
@section('content')
<x-report-shell
    title="حركة كرت الصنف"
    subtitle="سجل الحركات التفصيلية لمنتج محدد مع الرصيد المتراكم"
    :export-route="$product ? route('reports.inventory.stock-ledger.export', request()->query()) : null"
>
    <x-slot:filters>
        <form method="GET" action="{{ route('reports.inventory.stock-ledger') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">المنتج <span class="text-danger">*</span></label>
                <select name="product_id" class="form-select" required>
                    <option value="">-- اختر منتجاً --</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                            {{ $prod->name }} @if($prod->code)({{ $prod->code }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">من تاريخ</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', $from->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', $to->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> عرض
                </button>
            </div>
        </form>
    </x-slot:filters>

    @if($product)
        <x-slot:kpis>
            @php
                $totalIn  = $movements->where('direction', 'in')->sum(fn($m) => (float)$m->quantity);
                $totalOut = $movements->where('direction', 'out')->sum(fn($m) => (float)$m->quantity);
                $closing  = $movements->isNotEmpty() ? (float)$movements->last()->running_balance : (float)$openingBalance;
            @endphp
            <div class="col-md-3">
                <div class="rpt-stat-card">
                    <div class="rpt-stat-icon blue"><i class="fas fa-history"></i></div>
                    <div class="rpt-stat-value">{{ number_format($openingBalance, 2) }}</div>
                    <div class="rpt-stat-label">رصيد افتتاحي</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="rpt-stat-card">
                    <div class="rpt-stat-icon green"><i class="fas fa-arrow-down"></i></div>
                    <div class="rpt-stat-value">{{ number_format($totalIn, 2) }}</div>
                    <div class="rpt-stat-label">إجمالي الوارد</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="rpt-stat-card">
                    <div class="rpt-stat-icon red"><i class="fas fa-arrow-up"></i></div>
                    <div class="rpt-stat-value">{{ number_format($totalOut, 2) }}</div>
                    <div class="rpt-stat-label">إجمالي الصادر</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="rpt-stat-card">
                    <div class="rpt-stat-icon purple"><i class="fas fa-balance-scale"></i></div>
                    <div class="rpt-stat-value">{{ number_format($closing, 2) }}</div>
                    <div class="rpt-stat-label">رصيد ختامي</div>
                </div>
            </div>
        </x-slot:kpis>

        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold">{{ $product->name }}</h6>
                @if($product->code)
                    <small class="text-muted">الكود: {{ $product->code }}</small>
                @endif
            </div>
            <small class="text-muted">{{ $movements->count() }} حركة</small>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>نوع الحركة</th>
                        <th>الاتجاه</th>
                        <th>المرجع</th>
                        <th class="text-end">الكمية</th>
                        <th class="text-end">سعر الوحدة</th>
                        <th class="text-end">إجمالي التكلفة</th>
                        <th class="text-end">الرصيد المتراكم</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opening balance row --}}
                    <tr class="table-light">
                        <td colspan="4" class="fw-bold text-muted fst-italic">رصيد افتتاحي قبل الفترة</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-end fw-bold">{{ number_format($openingBalance, 2) }}</td>
                    </tr>
                    @forelse($movements as $m)
                    <tr>
                        <td>
                            {{ $m->movement_date instanceof \Carbon\Carbon ? $m->movement_date->format('Y-m-d') : $m->movement_date }}
                        </td>
                        <td>{{ $m->movement_type }}</td>
                        <td>
                            @if($m->direction === 'in')
                                <span class="badge bg-success-subtle text-success rpt-positive">
                                    <i class="fas fa-arrow-down me-1"></i>وارد
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger rpt-negative">
                                    <i class="fas fa-arrow-up me-1"></i>صادر
                                </span>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $m->reference_number ?? '-' }}</small></td>
                        <td class="text-end {{ $m->direction === 'in' ? 'rpt-positive' : 'rpt-negative' }}">
                            {{ $m->direction === 'in' ? '+' : '-' }}{{ number_format($m->quantity, 2) }}
                        </td>
                        <td class="text-end">{{ number_format($m->unit_cost, 2) }}</td>
                        <td class="text-end">{{ number_format($m->total_cost, 2) }}</td>
                        <td class="text-end fw-bold {{ (float)$m->running_balance < 0 ? 'rpt-negative' : '' }}">
                            {{ number_format($m->running_balance, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="rpt-empty">
                            <i class="fas fa-inbox d-block mb-2"></i>لا توجد حركات في هذه الفترة
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="card-body">
            <div class="rpt-empty py-5">
                <i class="fas fa-search d-block mb-3" style="font-size:3rem; opacity:0.3;"></i>
                <h5 class="text-muted mb-2">اختر منتجاً لعرض كرت الحركة</h5>
                <p class="text-muted">حدد المنتج والفترة الزمنية من خلال الفلاتر أعلاه ثم اضغط "عرض"</p>
            </div>
        </div>
    @endif
</x-report-shell>
@endsection
