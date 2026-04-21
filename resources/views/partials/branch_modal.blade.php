@php
    $isEditingBranch = (bool) $branch;
    $activeBranchModalId = old('branch_modal');
    $useOldBranchInput = $activeBranchModalId === $modalId;
    $branchNameValue = $useOldBranchInput ? old('name', $branch?->name) : $branch?->name;
    $branchCodeValue = $useOldBranchInput ? old('code', $branch?->code) : $branch?->code;
    $branchCityValue = $useOldBranchInput ? old('city', $branch?->city) : $branch?->city;
    $branchDefaultValue = $useOldBranchInput ? old('is_default', $branch?->is_default) : $branch?->is_default;

    $initials = $isEditingBranch ? mb_strtoupper(mb_substr($branchNameValue ?? '', 0, 1)) : 'BR';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down user-editor-dialog">
        <div class="modal-content user-editor-modal">
            <div class="modal-header user-editor-header">
                <div class="user-editor-heading">
                    <div class="user-editor-avatar">{{ $initials }}</div>
                    <div>
                        <div class="user-editor-eyebrow">{{ $isEditingBranch ? 'تحديث بيانات الفرع' : 'تأسيس فرع جديد' }}</div>
                        <h5 class="modal-title mb-1">{{ $modalTitle }}</h5>
                        <p class="user-editor-subtitle mb-0">حدد موقع الفرع والمسمى الإداري لتنظيم العمليات والتقارير.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if ($isEditingBranch)
                    @method('PUT')
                @endif
                <input type="hidden" name="branch_modal" value="{{ $modalId }}">
                <div class="modal-body user-editor-body">
                    <div class="user-editor-overview">
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">الحالة</span>
                            <strong>{{ $branchDefaultValue ? 'فرع افتراضي' : 'فرع إضافي' }}</strong>
                        </div>
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">المدينة</span>
                            <strong>{{ $branchCityValue ?: 'غير محدد' }}</strong>
                        </div>
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">المعرف</span>
                            <strong>{{ $branchCodeValue ?: '-' }}</strong>
                        </div>
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">الوضع</span>
                            <strong>{{ $isEditingBranch ? 'تعديل نشط' : 'إنشاء جديد' }}</strong>
                        </div>
                    </div>

                    <div class="user-editor-panel-highlight p-4 rounded-4 bg-white border">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">اسم الفرع</label>
                                <input type="text" name="name" class="form-control" value="{{ $branchNameValue }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">المدينة</label>
                                <input type="text" name="city" class="form-control" value="{{ $branchCityValue }}">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="branchDefault{{ $modalId }}" @checked($branchDefaultValue)>
                                    <label class="form-check-label fw-bold ms-2" for="branchDefault{{ $modalId }}">تعيين كفرع افتراضي (يُنسب إليه المخزون والمبيعات تلقائيًا)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer user-editor-footer">
                    <button type="button" class="btn btn-light user-editor-cancel" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary user-editor-submit">{{ $isEditingBranch ? 'حفظ التعديلات' : 'إضافة الفرع' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
