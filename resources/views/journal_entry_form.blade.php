@extends('layouts.app')

@section('title', 'إنشاء قيد محاسبي')

@php
    $canManageJournalEntries = auth()->user()->hasPermission('manage_journal_entries');
@endphp

@push('styles')
<style>
.journal-form {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.journal-form .form-control,
.journal-form .form-select {
    border-radius: 10px;
    padding: 12px;
    border: 2px solid #e0e0e0;
}

.btn-save {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    color: #fff;
    font-weight: 700;
}

.journal-line,
.balance-info {
    border-radius: 12px;
    padding: 20px;
}

.journal-line {
    background: #f8f9fa;
}

.line-row {
    background: #fff;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #e0e0e0;
}

.balance-info {
    background: linear-gradient(45deg, #e8f5e8, #f0f8f0);
    margin-top: 20px;
}

.debit {
    color: #dc3545;
    font-weight: 700;
}

.credit {
    color: #28a745;
    font-weight: 700;
}

/* Select2 overrides for Bootstrap 5 */
.select2-container .select2-selection--single {
    height: 48px;
    border-radius: 10px;
    border: 2px solid #e0e0e0;
    padding: 8px 12px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
    color: #495057;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px;
    right: 10px;
}
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="fas fa-book-medical"></i> {{ isset($journalEntry) ? 'تعديل مسودة القيد' : 'إنشاء قيد محاسبي جديد' }}</h2>
        <p class="text-muted mt-2 mb-0">{{ isset($journalEntry) ? 'تعديل قيد يدوي غير مرحل' : 'إضافة قيد يومي يدوي بنفس هيكلة صفحة Flask الأصلية' }}</p>
    </div>
    <a href="{{ route('journal_entries') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-right ms-2"></i>العودة للقيود
    </a>
</div>

<div class="journal-form">
    @php
        if (old('line_account')) {
            $manualLines = collect(old('line_account'))->map(function ($accountId, $index) {
                return (object) [
                    'account_id' => $accountId,
                    'description' => old('line_description.' . $index),
                    'debit' => old('line_debit.' . $index, 0),
                    'credit' => old('line_credit.' . $index, 0),
                ];
            });
        } elseif (isset($journalEntry)) {
            $manualLines = $journalEntry->lines;
        } else {
            $manualLines = collect([(object)['account_id'=>'', 'description'=>'', 'debit'=>0, 'credit'=>0], (object)['account_id'=>'', 'description'=>'', 'debit'=>0, 'credit'=>0]]);
        }
    @endphp
    <form method="POST" action="{{ isset($journalEntry) ? route('journal_entries.update', $journalEntry) : route('journal_entries.store') }}" data-journal-form>
        @csrf
        @if(isset($journalEntry))
            @method('PUT')
        @endif
        <div class="row mb-4">
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">رقم القيد</label>
                <input type="text" class="form-control" value="{{ $nextEntryNumber }}" readonly>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">التاريخ *</label>
                <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', isset($journalEntry) ? \Carbon\Carbon::parse($journalEntry->entry_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">المرجع</label>
                <input type="text" name="reference" class="form-control" placeholder="رقم المستند أو المرجع" value="{{ old('reference', $suggestedJournalReference) }}">
                <small class="text-muted d-block mt-2">يتم توليد المرجع تلقائيًا ويمكنك تعديله إذا لزم.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">الوصف *</label>
                <input type="text" name="description" class="form-control" placeholder="وصف القيد" value="{{ old('description', $journalEntry->description ?? '') }}" required>
            </div>
        </div>

        <div class="journal-line">
            <h5 class="mb-3"><i class="fas fa-list ms-2 text-primary"></i>بنود القيد <small class="text-muted me-2">(يجب أن يتساوى المدين والدائن)</small></h5>

            <div id="linesContainer">
                @foreach ($manualLines as $line)
                    <div class="line-row" data-journal-line-row>
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">الحساب *</label>
                                <select name="line_account[]" class="form-select select2-account">
                                    <option value="">اختر الحساب</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" {{ (string) $line->account_id === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label">الوصف</label>
                                <input type="text" name="line_description[]" class="form-control" placeholder="وصف البند" value="{{ $line->description }}">
                            </div>
                            <div class="col-md-2 mb-3 mb-md-0">
                                <label class="form-label">مدين</label>
                                <input type="number" name="line_debit[]" class="form-control line-debit" value="{{ $line->debit }}" min="0" step="0.01" lang="en" dir="ltr">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">دائن</label>
                                <input type="number" name="line_credit[]" class="form-control line-credit" value="{{ $line->credit }}" min="0" step="0.01" lang="en" dir="ltr">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-line w-100"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                @if ($canManageJournalEntries)
                    <button type="button" class="btn btn-outline-primary" id="addJournalLine">
                        <i class="fas fa-plus ms-2"></i>إضافة بند جديد
                    </button>
                @endif
            </div>
        </div>

        <div class="balance-info">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label">إجمالي المدين</label>
                    <h4 id="totalDebit" class="debit">0.00 {{ $company->currency }}</h4>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label">إجمالي الدائن</label>
                    <h4 id="totalCredit" class="credit">0.00 {{ $company->currency }}</h4>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الرصيد</label>
                    <h4 id="balanceStatus" class="text-success">0.00 {{ $company->currency }}</h4>
                </div>
            </div>
            <div id="balanceWarning" class="alert alert-warning mt-3 d-none">
                <i class="fas fa-exclamation-triangle ms-2"></i>القيد غير متوازن. يجب أن يتساوى إجمالي المدين والدائن.
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex gap-2 flex-wrap">
                    @if ($canManageJournalEntries)
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save ms-2"></i>حفظ القيد
                        </button>
                    @endif
                    <a href="{{ route('journal_entries') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times ms-2"></i>إلغاء
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function initSelect2(element) {
    $(element).select2({
        placeholder: "ابحث بالاسم أو الكود للحساب",
        allowClear: true,
        width: '100%',
        dir: 'rtl'
    });
}

function recalculateJournalBalance(form) {
    let totalDebit = 0;
    let totalCredit = 0;

    form.querySelectorAll('.line-debit').forEach((input) => {
        totalDebit += Number(input.value || 0);
    });

    form.querySelectorAll('.line-credit').forEach((input) => {
        totalCredit += Number(input.value || 0);
    });

    const balance = totalDebit - totalCredit;
    form.querySelector('#totalDebit').textContent = `${totalDebit.toFixed(2)} {{ $company->currency }}`;
    form.querySelector('#totalCredit').textContent = `${totalCredit.toFixed(2)} {{ $company->currency }}`;

    const balanceStatus = form.querySelector('#balanceStatus');
    balanceStatus.textContent = `${Math.abs(balance).toFixed(2)} {{ $company->currency }}`;
    balanceStatus.className = balance === 0 ? 'text-success' : 'text-danger';

    form.querySelector('#balanceWarning').classList.toggle('d-none', balance === 0);
}

function bindJournalLine(row, form) {
    row.querySelectorAll('.line-debit, .line-credit').forEach((input) => {
        input.addEventListener('input', () => recalculateJournalBalance(form));
    });

    row.querySelector('.remove-line')?.addEventListener('click', () => {
        if (form.querySelectorAll('[data-journal-line-row]').length > 2) {
            // Destroy select2 before removing to prevent memory leaks
            const selectEl = row.querySelector('.select2-account');
            if (selectEl && $(selectEl).hasClass("select2-hidden-accessible")) {
                $(selectEl).select2('destroy');
            }
            
            row.remove();
            recalculateJournalBalance(form);
        }
    });
}

function addJournalLine(form) {
    const firstRow = form.querySelector('[data-journal-line-row]');
    const container = form.querySelector('#linesContainer');
    if (!firstRow || !container) {
        return;
    }
    
    // Destroy select2 on original row temporarily before cloning if needed, 
    // or just clone and strip select2 classes
    
    const selectToClone = firstRow.querySelector('.select2-account');
    const wasSelect2 = $(selectToClone).hasClass("select2-hidden-accessible");
    if (wasSelect2) {
        $(selectToClone).select2('destroy');
    }

    const clone = firstRow.cloneNode(true);
    
    // Restore select2 on original row
    if (wasSelect2) {
        initSelect2(selectToClone);
    }

    // Clean up clone
    clone.querySelectorAll('.select2-container').forEach(e => e.remove());
    clone.querySelectorAll('select').forEach((select) => {
        select.classList.remove('select2-hidden-accessible');
        select.removeAttribute('data-select2-id');
        select.selectedIndex = 0;
    });
    
    clone.querySelectorAll('input').forEach((input) => {
        input.value = input.classList.contains('line-debit') || input.classList.contains('line-credit') ? '0' : '';
    });

    container.appendChild(clone);
    initSelect2(clone.querySelector('.select2-account'));
    
    bindJournalLine(clone, form);
    recalculateJournalBalance(form);
    
    // Focus the new select if possible
    $(clone.querySelector('.select2-account')).select2('open');
}

document.querySelectorAll('[data-journal-form]').forEach((form) => {
    // Init Select2 on all existing rows
    form.querySelectorAll('.select2-account').forEach(select => initSelect2(select));

    form.querySelectorAll('[data-journal-line-row]').forEach((row) => bindJournalLine(row, form));
    
    const addBtn = form.querySelector('#addJournalLine');
    if(addBtn) {
        addBtn.addEventListener('click', () => addJournalLine(form));
    }
    recalculateJournalBalance(form);
});

// Keyboard Shortcuts
document.addEventListener('keydown', function(event) {
    // Ctrl + S (Save)
    if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault();
        const form = document.querySelector('[data-journal-form]');
        if (form && document.querySelector('.btn-save')) {
            form.submit();
        }
    }
    // Ctrl + E or Ctrl + Enter (Add Line)
    if ((event.ctrlKey || event.metaKey) && (event.key === 'e' || event.key === 'Enter')) {
        event.preventDefault();
        const form = document.querySelector('[data-journal-form]');
        if(form && document.querySelector('#addJournalLine')) {
            addJournalLine(form);
        }
    }
});
</script>
@endpush
