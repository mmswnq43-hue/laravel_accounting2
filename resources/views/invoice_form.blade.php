@extends('layouts.app')

@php
    $canManageInvoices = auth()->user()->hasPermission('manage_invoices');
    $defaultTaxRate = $defaultTaxRate ?? 15; // fallback when controller does not provide rate
    $isEditingInvoice = isset($invoice) && $invoice->exists;
    $invoiceDateValue = old('invoice_date', $isEditingInvoice ? optional($invoice->invoice_date)->format('Y-m-d') : now()->format('Y-m-d'));
    $dueDateValue = old('due_date', $isEditingInvoice ? optional($invoice->due_date)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'));
    $customerIdValue = old('customer_id', $isEditingInvoice ? $invoice->customer_id : null);
    $salesChannelIdValue = old('sales_channel_id', $isEditingInvoice ? $invoice->sales_channel_id : ($defaultSalesChannelId ?? null));
    $revenueAccountIdValue = old('revenue_account_id', $isEditingInvoice ? $invoice->revenue_account_id : null);
    $paymentStatusValue = old('payment_status', $isEditingInvoice ? match ($invoice->payment_status) { 'paid' => 'full', 'pending' => 'deferred', default => $invoice->payment_status } : 'deferred');
    $paymentAccountIdValue = old('payment_account_id', $isEditingInvoice ? $invoice->payment_account_id : null);
    $paidAmountValue = old('paid_amount', $isEditingInvoice ? number_format((float) $invoice->paid_amount, 2, '.', '') : '0');
    $invoiceStatusValue = old('status', $isEditingInvoice ? $invoice->status : 'sent');
    $notesValue = old('notes', $isEditingInvoice ? $invoice->notes : '');
    $termsValue = old('terms', $isEditingInvoice ? $invoice->terms : '');
@endphp

@section('title', $isEditingInvoice ? 'تعديل عملية البيع' : 'إضافة مبيعات')

@push('styles')
<style>
.invoice-form {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.invoice-form .form-control,
.invoice-form .form-select {
    border-radius: 10px;
    padding: 12px;
    border: 2px solid #e0e0e0;
    transition: all 0.3s ease;
}

.invoice-form .form-control:focus,
.invoice-form .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.18);
}

.btn-save {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    color: #fff;
    font-weight: 700;
}

.invoice-item,
.tax-info {
    border-radius: 12px;
    padding: 20px;
}

.invoice-item {
    background: #f8f9fa;
}

.item-row {
    background: #fff;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #e0e0e0;
}

.tax-info {
    background: linear-gradient(45deg, #e8f5e8, #f0f8f0);
    margin-top: 20px;
}

/* Searchable Select Styles */
.search-wrapper {
    position: relative;
}
.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 0 0 10px 10px;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-top: -2px;
}
.search-result-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.search-result-item:last-child {
    border-bottom: none;
}
.search-result-item:hover {
    background: #f8f9ff;
    color: #667eea;
}
.search-result-item .small {
    font-size: 0.8rem;
}
.selected-badge {
    display: inline-flex;
    align-items: center;
    background: #eef2ff;
    color: #4f46e5;
    padding: 4px 12px;
    border-radius: 20px;
    margin-top: 8px;
    font-size: 0.85rem;
    font-weight: 600;
}
.selected-badge .btn-close {
    font-size: 0.65rem;
    margin-right: 8px;
    padding: 0;
}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="fas fa-plus-circle"></i> {{ $isEditingInvoice ? 'تعديل عملية البيع' : 'إضافة مبيعات' }}</h2>
        <p class="text-muted mt-2 mb-0">{{ $isEditingInvoice ? 'تحديث بيانات عملية البيع الحالية مع الحفاظ على سلامة المخزون.' : 'إضافة مبيعات جديدة بنفس هيكلة واجهة Flask' }}</p>
    </div>
    <a href="{{ route('invoices') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-right ms-2"></i>العودة للمبيعات
    </a>
</div>

<div class="invoice-form">
    @php
        $invoiceItems = collect(old('item_description', []))->map(function ($description, $index) use ($defaultTaxRate) {
            return (object) [
                'product_id' => old('item_product_id.' . $index),
                'description' => $description,
                'quantity' => old('item_quantity.' . $index, 1),
                'unit_price' => old('item_price.' . $index, 0),
                'tax_rate' => old('item_tax_rate.' . $index, $defaultTaxRate),
            ];
        });

        if ($invoiceItems->isEmpty()) {
            $invoiceItems = $isEditingInvoice
                ? $invoice->items->map(function ($item) {
                    return (object) [
                        'product_id' => $item->product_id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'tax_rate' => $item->tax_rate,
                    ];
                })
                : collect([(object) [
                    'product_id' => null,
                    'description' => '',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'tax_rate' => $defaultTaxRate,
                ]]);
        }
    @endphp
    <form method="POST" action="{{ $isEditingInvoice ? route('invoices.update', $invoice) : route('invoices.store') }}" enctype="multipart/form-data" data-invoice-form>
        @csrf
        @if ($isEditingInvoice)
            @method('PUT')
        @endif
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="form-label">العميل *</label>
                <div class="d-flex gap-2 align-items-start">
                    <div class="search-wrapper flex-grow-1">
                        <input type="text" id="customerSearchInput" class="form-control" placeholder="ابحث عن عميل بالاسم أو الكود..." autocomplete="off">
                        <input type="hidden" name="customer_id" id="selectedCustomerId" value="{{ $customerIdValue }}" required>
                        <div id="customerSearchResults" class="search-results d-none"></div>
                        <div id="selectedCustomerBadge" class="selected-badge d-none">
                            <span id="selectedCustomerName"></span>
                            <button type="button" class="btn-close" id="clearCustomerBtn"></button>
                        </div>
                    </div>
                    @if ($canManageInvoices)
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal" title="إضافة عميل جديد">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">تاريخ الفاتورة *</label>
                <input type="date" name="invoice_date" class="form-control" value="{{ $invoiceDateValue }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">تاريخ الاستحقاق</label>
                <input type="date" name="due_date" class="form-control" value="{{ $dueDateValue }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select">
                    <option value="draft" {{ $invoiceStatusValue === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="sent" {{ $invoiceStatusValue === 'sent' ? 'selected' : '' }}>مرسلة</option>
                </select>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <label class="form-label">ملكية عملية البيع</label>
                <div class="border rounded-3 p-3 bg-light-subtle h-100">
                    <div class="fw-semibold mb-1">المستخدم: {{ $salesOwnerContext['user_name'] ?: 'غير محدد' }}</div>
                    <div class="small text-muted mb-1">الموظف: {{ $salesOwnerContext['employee_name'] ?: 'غير مرتبط' }}</div>
                    <div class="small text-muted">الفرع: {{ $salesOwnerContext['branch_name'] ?: 'غير مرتبط' }}</div>
                    @if (! empty($salesOwnerContext['warning']))
                        <div class="alert alert-warning mt-3 mb-0 py-2">{{ $salesOwnerContext['warning'] }}</div>
                    @endif
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">قناة البيع *</label>
                <select name="sales_channel_id" class="form-select" required>
                    <option value="">اختر قناة البيع</option>
                    @foreach ($salesChannels as $salesChannel)
                        <option value="{{ $salesChannel->id }}" {{ (string) $salesChannelIdValue === (string) $salesChannel->id ? 'selected' : '' }}>{{ $salesChannel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">حساب الإيراد (الافتراضي مبيعات)</label>
                <select name="revenue_account_id" class="form-select @error('revenue_account_id') is-invalid @enderror">
                    <option value="">توجيه آلي حسب الصنف</option>
                    @foreach ($revenueAccounts ?? [] as $account)
                        <option value="{{ $account->id }}" {{ (string) $revenueAccountIdValue === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name_ar ?? $account->name }}</option>
                    @endforeach
                </select>
                @error('revenue_account_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">حالة الدفع *</label>
                <select name="payment_status" class="form-select" data-payment-status required>
                    <option value="deferred" {{ $paymentStatusValue === 'deferred' ? 'selected' : '' }}>آجل</option>
                    <option value="partial" {{ $paymentStatusValue === 'partial' ? 'selected' : '' }}>دفع جزئي</option>
                    <option value="full" {{ $paymentStatusValue === 'full' ? 'selected' : '' }}>دفع كامل</option>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0" data-paid-amount-wrapper>
                <label class="form-label">المبلغ المدفوع</label>
                <input type="number" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ $paidAmountValue }}" min="0" step="0.01" data-paid-amount lang="en" dir="ltr">
                @error('paid_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">حساب التحصيل</label>
                <select name="payment_account_id" class="form-select @error('payment_account_id') is-invalid @enderror">
                    <option value="">اختر الحساب</option>
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}" {{ (string) $paymentAccountIdValue === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name_ar ?? $account->name }}</option>
                    @endforeach
                </select>
                @error('payment_account_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">المبلغ المتبقي</label>
                <input type="text" class="form-control" value="0.00 {{ $company->currency }}" data-balance-due readonly>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">مرفق الفاتورة (اختياري)</label>
                <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror" accept="application/pdf,image/*">
                @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @if ($isEditingInvoice && $invoice->attachment_path)
                    <div class="form-text"><a href="{{ route('invoices.attachment', $invoice) }}" target="_blank" rel="noopener">عرض المرفق الحالي</a></div>
                @else
                    <div class="form-text">المرفق اختياري.</div>
                @endif
            </div>
        </div>

        <div class="invoice-item">
            <h5 class="mb-3"><i class="fas fa-list ms-2 text-primary"></i>بنود الفاتورة</h5>
            <div id="itemsContainer">
                @foreach ($invoiceItems as $item)
                    <div class="item-row" data-invoice-item-row>
                        <div class="row align-items-center g-3">
                            <div class="col-md-3">
                                <label class="form-label">المنتج</label>
                                <select name="item_product_id[]" class="form-select invoice-product-select">
                                    <option value="">اختر المنتج</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-description="{{ $product->description ?? '' }}" data-sell-price="{{ $product->sell_price ?? 0 }}" data-stock-quantity="{{ $product->stock_quantity ?? 0 }}" data-product-type="{{ $product->type }}" data-product-name="{{ $product->name }}" {{ (string) $item->product_id === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}{{ $product->type !== 'service' ? ' - المتاح: ' . number_format((float) $product->stock_quantity, 2) : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الوصف *</label>
                                <input type="text" name="item_description[]" class="form-control invoice-item-description" value="{{ $item->description }}" placeholder="اكتب الوصف أو اختر منتج" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">الكمية *</label>
                                @php $quantityErrorKey = 'item_quantity.' . $loop->index; @endphp
                                <input type="number" name="item_quantity[]" class="form-control invoice-item-quantity @error($quantityErrorKey) is-invalid @enderror" value="{{ $item->quantity }}" min="0.01" step="0.01" required lang="en" dir="ltr">
                                @error($quantityErrorKey)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text text-danger d-none" data-stock-feedback></div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">السعر *</label>
                                <input type="number" name="item_price[]" class="form-control invoice-item-price" value="{{ $item->unit_price }}" min="0" step="0.01" required lang="en" dir="ltr">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">الضريبة %</label>
                                <input type="number" name="item_tax_rate[]" class="form-control invoice-item-tax" value="{{ $item->tax_rate }}" min="0" step="0.1" lang="en" dir="ltr">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">الإجمالي</label>
                                <input type="text" class="form-control invoice-item-total" readonly value="0.00">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="button" class="btn btn-outline-danger w-100" data-remove-invoice-item><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                @if ($canManageInvoices)
                    <button type="button" class="btn btn-outline-primary" id="addInvoiceItem">
                        <i class="fas fa-plus ms-2"></i>إضافة بند جديد
                    </button>
                @endif
            </div>
        </div>

        <div class="alert alert-warning d-none mt-3" data-invoice-stock-warning role="alert"></div>

        <div class="row mt-4">
            <div class="col-md-12">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات إضافية...">{{ $notesValue }}</textarea>
            </div>
            <div class="col-md-12 mt-3">
                <label class="form-label">الشروط</label>
                <textarea name="terms" class="form-control" rows="2" placeholder="شروط السداد أو شروط الفاتورة">{{ $termsValue }}</textarea>
            </div>
        </div>

        <div class="tax-info">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">المجموع الفرعي</label>
                    <h4 id="subtotal">0.00 {{ $company->currency }}</h4>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">ضريبة القيمة المضافة</label>
                    <h4 id="taxAmount">0.00 {{ $company->currency }}</h4>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">الخصم</label>
                    <input type="number" id="discountAmount" class="form-control" value="0" min="0" step="0.01" disabled lang="en" dir="ltr">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الإجمالي</label>
                    <h4 id="totalAmount" class="text-primary">0.00 {{ $company->currency }}</h4>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">المبلغ المدفوع</label>
                    <h4 id="paidAmountSummary">0.00 {{ $company->currency }}</h4>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المتبقي</label>
                    <h4 id="balanceDueSummary" class="text-danger">0.00 {{ $company->currency }}</h4>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex gap-2 flex-wrap">
                    @if ($canManageInvoices)
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save ms-2"></i>{{ $isEditingInvoice ? 'حفظ التعديلات' : 'حفظ الفاتورة' }}
                        </button>
                    @endif
                    <a href="{{ route('invoices') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times ms-2"></i>إلغاء
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@if ($canManageInvoices)
    <!-- نافذة إضافة عميل جديد سريع -->
    <div class="modal fade" id="quickAddCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة عميل جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickAddCustomerForm">
                    @csrf
                    <div class="modal-body">
                        <div id="quickCustomerErrors" class="alert alert-danger d-none"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الاسم</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الاسم بالعربي</label>
                                <input type="text" name="name_ar" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحالة</label>
                                <select name="is_active" class="form-select">
                                    <option value="1">نشط</option>
                                    <option value="0">غير نشط</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الهاتف</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الجوال</label>
                                <input type="text" name="mobile" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المدينة</label>
                                <select name="city" class="form-select">
                                    <option value="">اختر المدينة</option>
                                    @foreach ($companyCities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">الدولة المعتمدة: {{ $companyCountryLabel }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الدولة</label>
                                <input type="text" class="form-control" value="{{ $companyCountryLabel }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الرقم الضريبي</label>
                                <input type="text" name="tax_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحد الائتماني</label>
                                <input type="number" name="credit_limit" class="form-control" min="0" step="0.01" value="0" lang="en" dir="ltr">
                            </div>
                            <div class="col-12">
                                <label class="form-label">العنوان</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="saveQuickCustomerBtn">إضافة العميل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
function invoiceNumericValue(value) {
    const parsedValue = Number.parseFloat(value);

    return Number.isFinite(parsedValue) ? parsedValue : 0;
}

function recalculateInvoiceTotals(form) {
    let subtotal = 0;
    let taxAmount = 0;

    form.querySelectorAll('[data-invoice-item-row]').forEach((row) => {
        const quantity = invoiceNumericValue(row.querySelector('.invoice-item-quantity')?.value);
        const price = invoiceNumericValue(row.querySelector('.invoice-item-price')?.value);
        const taxRate = invoiceNumericValue(row.querySelector('.invoice-item-tax')?.value);
        const lineSubtotal = quantity * price;
        const lineTax = lineSubtotal * (taxRate / 100);
        const lineTotal = lineSubtotal + lineTax;

        subtotal += lineSubtotal;
        taxAmount += lineTax;

        const totalField = row.querySelector('.invoice-item-total');
        if (totalField) {
            totalField.value = lineTotal.toFixed(2);
        }
    });

    const discount = invoiceNumericValue(form.querySelector('#discountAmount')?.value);
    const total = subtotal + taxAmount - discount;

    form.querySelector('#subtotal').textContent = `${subtotal.toFixed(2)} {{ $company->currency }}`;
    form.querySelector('#taxAmount').textContent = `${taxAmount.toFixed(2)} {{ $company->currency }}`;
    form.querySelector('#totalAmount').textContent = `${total.toFixed(2)} {{ $company->currency }}`;
    syncInvoicePaymentFields(form, total);
    updateInvoiceStockWarnings(form);
}

function syncInvoicePaymentFields(form, totalAmount) {
    const paymentStatusField = form.querySelector('[data-payment-status]');
    const paidAmountField = form.querySelector('[data-paid-amount]');
    const balanceField = form.querySelector('[data-balance-due]');
    const paidSummary = form.querySelector('#paidAmountSummary');
    const balanceSummary = form.querySelector('#balanceDueSummary');
    const paidAmountWrapper = form.querySelector('[data-paid-amount-wrapper]');

    if (!paymentStatusField || !paidAmountField) {
        return;
    }

    if (paymentStatusField.value === 'full') {
        paidAmountField.value = totalAmount.toFixed(2);
        paidAmountField.readOnly = true;
        paidAmountWrapper?.classList.add('d-none');
    } else if (paymentStatusField.value === 'deferred') {
        paidAmountField.value = '0.00';
        paidAmountField.readOnly = true;
        paidAmountWrapper?.classList.add('d-none');
    } else {
        paidAmountField.readOnly = false;
        paidAmountWrapper?.classList.remove('d-none');
        const partialValue = Math.min(invoiceNumericValue(paidAmountField.value), totalAmount);
        paidAmountField.value = partialValue.toFixed(2);
    }

    const paidAmount = Math.min(invoiceNumericValue(paidAmountField.value), totalAmount);
    const balanceDue = Math.max(totalAmount - paidAmount, 0);

    if (balanceField) {
        balanceField.value = `${balanceDue.toFixed(2)} {{ $company->currency }}`;
    }

    if (paidSummary) {
        paidSummary.textContent = `${paidAmount.toFixed(2)} {{ $company->currency }}`;
    }

    if (balanceSummary) {
        balanceSummary.textContent = `${balanceDue.toFixed(2)} {{ $company->currency }}`;
        balanceSummary.classList.toggle('text-success', balanceDue <= 0);
        balanceSummary.classList.toggle('text-danger', balanceDue > 0);
    }
}

function invoiceSelectedProductOption(row) {
    const select = row.querySelector('.invoice-product-select');

    if (!select || select.selectedIndex < 0) {
        return null;
    }

    return select.options[select.selectedIndex] || null;
}

function setInvoiceRowStockMessage(row, message) {
    const quantityInput = row.querySelector('.invoice-item-quantity');
    const feedback = row.querySelector('[data-stock-feedback]');

    if (quantityInput) {
        quantityInput.classList.toggle('is-invalid', Boolean(message));
    }

    if (!feedback) {
        return;
    }

    if (message) {
        feedback.textContent = message;
        feedback.classList.remove('d-none');

        return;
    }

    feedback.textContent = '';
    feedback.classList.add('d-none');
}

function updateInvoiceStockWarnings(form) {
    const groupedProducts = new Map();
    const warningBox = form.querySelector('[data-invoice-stock-warning]');

    form.querySelectorAll('[data-invoice-item-row]').forEach((row) => {
        setInvoiceRowStockMessage(row, '');

        const selectedOption = invoiceSelectedProductOption(row);
        if (!selectedOption || !selectedOption.value || selectedOption.dataset.productType === 'service') {
            return;
        }

        const quantity = invoiceNumericValue(row.querySelector('.invoice-item-quantity')?.value);
        if (quantity <= 0) {
            return;
        }

        const productId = selectedOption.value;
        const existingGroup = groupedProducts.get(productId) ?? {
            available: invoiceNumericValue(selectedOption.dataset.stockQuantity),
            name: selectedOption.dataset.productName || selectedOption.textContent.trim(),
            requested: 0,
            rows: [],
        };

        existingGroup.requested += quantity;
        existingGroup.rows.push(row);
        groupedProducts.set(productId, existingGroup);
    });

    const messages = [];

    groupedProducts.forEach((group) => {
        if (group.requested <= group.available) {
            return;
        }

        const message = group.available > 0
            ? `الكمية المتاحة للمنتج "${group.name}" هي ${group.available.toFixed(2)} فقط، بينما إجمالي الكمية المطلوبة ${group.requested.toFixed(2)}.`
            : `المنتج "${group.name}" نفدت كميته الحالية ولا يمكن إضافته إلى الفاتورة.`;

        group.rows.forEach((row) => setInvoiceRowStockMessage(row, message));
        messages.push(message);
    });

    if (warningBox) {
        if (messages.length > 0) {
            warningBox.innerHTML = messages.map((message) => `<div>${message}</div>`).join('');
            warningBox.classList.remove('d-none');
        } else {
            warningBox.innerHTML = '';
            warningBox.classList.add('d-none');
        }
    }

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = messages.length > 0;
    });

    return messages.length === 0;
}

function applyInvoiceProduct(row, form) {
    const select = row.querySelector('.invoice-product-select');
    const descriptionInput = row.querySelector('.invoice-item-description');
    const priceInput = row.querySelector('.invoice-item-price');

    if (!select) {
        return;
    }

    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        recalculateInvoiceTotals(form);
        return;
    }

    if (descriptionInput && (!descriptionInput.value || descriptionInput.dataset.autoFilled === 'true')) {
        descriptionInput.value = selectedOption.dataset.description || selectedOption.textContent.trim();
        descriptionInput.dataset.autoFilled = 'true';
    }

    if (priceInput && (!priceInput.value || invoiceNumericValue(priceInput.value) === 0 || priceInput.dataset.autoFilled === 'true')) {
        priceInput.value = selectedOption.dataset.sellPrice || '0';
        priceInput.dataset.autoFilled = 'true';
    }

    recalculateInvoiceTotals(form);
}

function bindInvoiceRow(row, form) {
    row.querySelector('.invoice-product-select')?.addEventListener('change', () => applyInvoiceProduct(row, form));
    row.querySelector('.invoice-item-description')?.addEventListener('input', (event) => {
        event.target.dataset.autoFilled = 'false';
    });
    row.querySelector('.invoice-item-price')?.addEventListener('input', (event) => {
        event.target.dataset.autoFilled = 'false';
        recalculateInvoiceTotals(form);
    });
    row.querySelectorAll('.invoice-item-quantity, .invoice-item-tax').forEach((input) => {
        input.addEventListener('input', () => recalculateInvoiceTotals(form));
    });
    row.querySelector('[data-remove-invoice-item]')?.addEventListener('click', () => {
        if (form.querySelectorAll('[data-invoice-item-row]').length > 1) {
            row.remove();
            recalculateInvoiceTotals(form);
        }
    });

    if (row.querySelector('.invoice-product-select')?.value) {
        applyInvoiceProduct(row, form);
    }
}

function addInvoiceRow(form) {
    const firstRow = form.querySelector('[data-invoice-item-row]');
    const container = form.querySelector('#itemsContainer');

    if (!firstRow || !container) {
        return;
    }

    const clone = firstRow.cloneNode(true);
    clone.querySelectorAll('input').forEach((input) => {
        if (input.classList.contains('invoice-item-quantity')) {
            input.value = '1';
            input.classList.remove('is-invalid');
        } else if (input.classList.contains('invoice-item-tax')) {
            input.value = '{{ $defaultTaxRate }}';
        } else if (input.classList.contains('invoice-item-total')) {
            input.value = '0.00';
        } else {
            input.value = '';
        }
        delete input.dataset.autoFilled;
    });
    clone.querySelectorAll('[data-stock-feedback]').forEach((feedback) => {
        feedback.textContent = '';
        feedback.classList.add('d-none');
    });
    clone.querySelectorAll('select').forEach((select) => {
        select.selectedIndex = 0;
    });

    container.appendChild(clone);
    bindInvoiceRow(clone, form);
    recalculateInvoiceTotals(form);
}

document.querySelectorAll('[data-invoice-form]').forEach((form) => {
    form.querySelectorAll('[data-invoice-item-row]').forEach((row) => bindInvoiceRow(row, form));
    form.querySelector('#addInvoiceItem')?.addEventListener('click', () => addInvoiceRow(form));
    form.querySelector('[data-payment-status]')?.addEventListener('change', () => recalculateInvoiceTotals(form));
    form.querySelector('[data-paid-amount]')?.addEventListener('input', () => recalculateInvoiceTotals(form));
    form.addEventListener('submit', (event) => {
        if (updateInvoiceStockWarnings(form)) {
            return;
        }

        event.preventDefault();
        form.querySelector('.invoice-item-quantity.is-invalid')?.focus();
    });
    recalculateInvoiceTotals(form);
});

// مصفوفة العملاء الممررة من الخادم
let customersList = @json($customers);

const customerSearchInput = document.getElementById('customerSearchInput');
const customerSearchResults = document.getElementById('customerSearchResults');
const selectedCustomerId = document.getElementById('selectedCustomerId');
const selectedCustomerBadge = document.getElementById('selectedCustomerBadge');
const selectedCustomerName = document.getElementById('selectedCustomerName');
const clearCustomerBtn = document.getElementById('clearCustomerBtn');

function updateCustomerSelection(customer) {
    if (customer) {
        selectedCustomerId.value = customer.id;
        selectedCustomerName.textContent = customer.name;
        selectedCustomerBadge.classList.remove('d-none');
        customerSearchInput.classList.add('d-none');
        customerSearchInput.value = '';
        customerSearchResults.classList.add('d-none');
    } else {
        selectedCustomerId.value = '';
        selectedCustomerName.textContent = '';
        selectedCustomerBadge.classList.add('d-none');
        customerSearchInput.classList.remove('d-none');
        customerSearchInput.focus();
    }
}

// تهيئة الاختيار الأولي عند التحميل (في حالة التعديل أو العودة ببيانات قديمة)
if (selectedCustomerId.value) {
    const existing = customersList.find(c => String(c.id) === String(selectedCustomerId.value));
    if (existing) {
        updateCustomerSelection(existing);
    }
}

customerSearchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    if (query.length < 1) {
        customerSearchResults.classList.add('d-none');
        return;
    }

    const filtered = customersList.filter(c => 
        c.name.toLowerCase().includes(query) || 
        (c.name_ar && c.name_ar.toLowerCase().includes(query)) ||
        (c.code && c.code.toLowerCase().includes(query))
    ).slice(0, 10);

    if (filtered.length > 0) {
        customerSearchResults.innerHTML = filtered.map(c => `
            <div class="search-result-item" data-id="${c.id}">
                <div class="fw-bold">${c.name}</div>
                <div class="small text-muted">${c.code || ''} ${c.name_ar ? '| ' + c.name_ar : ''}</div>
            </div>
        `).join('');
        customerSearchResults.classList.remove('d-none');
    } else {
        customerSearchResults.innerHTML = '<div class="p-3 text-muted text-center">لا توجد نتائج</div>';
        customerSearchResults.classList.remove('d-none');
    }
});

customerSearchResults.addEventListener('click', function(e) {
    const item = e.target.closest('.search-result-item');
    if (item) {
        const id = item.dataset.id;
        const customer = customersList.find(c => String(c.id) === String(id));
        updateCustomerSelection(customer);
    }
});

clearCustomerBtn.addEventListener('click', function() {
    updateCustomerSelection(null);
});

// إغلاق النتائج عند النقر خارجها
document.addEventListener('click', function(e) {
    if (!customerSearchInput.contains(e.target) && !customerSearchResults.contains(e.target)) {
        customerSearchResults.classList.add('d-none');
    }
});

// إضافة عميل جديد عبر AJAX
const quickAddCustomerForm = document.getElementById('quickAddCustomerForm');
const quickCustomerErrors = document.getElementById('quickCustomerErrors');
const quickAddCustomerModal = document.getElementById('quickAddCustomerModal');

if (quickAddCustomerForm) {
    quickAddCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('saveQuickCustomerBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحفظ...';
        quickCustomerErrors.classList.add('d-none');

        const formData = new FormData(this);

        fetch("{{ route('customers.store') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 && body.success) {
                // إضافة العميل الجديد للمصفوفة المحلية
                customersList.push(body.customer);
                updateCustomerSelection(body.customer);
                
                // إظهار رسالة نجاح
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
                successAlert.innerHTML = `${body.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                document.querySelector('.invoice-form').prepend(successAlert);

                // إغلاق النافذة وتنظيف النموذج
                const modalInstance = bootstrap.Modal.getInstance(quickAddCustomerModal);
                modalInstance.hide();
                quickAddCustomerForm.reset();
            } else if (status === 422) {
                let errorHtml = '<ul class="mb-0">';
                Object.values(body.errors).forEach(errArray => {
                    errArray.forEach(err => { errorHtml += `<li>${err}</li>`; });
                });
                errorHtml += '</ul>';
                quickCustomerErrors.innerHTML = errorHtml;
                quickCustomerErrors.classList.remove('d-none');
            } else {
                quickCustomerErrors.textContent = 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.';
                quickCustomerErrors.classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            quickCustomerErrors.textContent = 'فشل الاتصال بالخادم.';
            quickCustomerErrors.classList.remove('d-none');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'إضافة العميل';
        });
    });
}
</script>
@endpush
