@php
    $isEditingSalesChannel = (bool) $salesChannel;
    $activeSalesChannelModalId = old('sales_channel_modal');
    $useOldSalesChannelInput = $activeSalesChannelModalId === $modalId;
    $salesChannelNameValue = $useOldSalesChannelInput ? old('name', $salesChannel?->name) : $salesChannel?->name;
    $salesChannelCodeValue = $useOldSalesChannelInput ? old('code', $salesChannel?->code) : $salesChannel?->code;
    $salesChannelDefaultValue = $useOldSalesChannelInput ? old('is_default', $salesChannel?->is_default) : $salesChannel?->is_default;

    $initials = $isEditingSalesChannel ? mb_strtoupper(mb_substr($salesChannelNameValue ?? '', 0, 1)) : 'CH';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down user-editor-dialog">
        <div class="modal-content user-editor-modal">
            <div class="modal-header user-editor-header">
                <div class="user-editor-heading">
                    <div class="user-editor-avatar">{{ $initials }}</div>
                    <div>
                        <div class="user-editor-eyebrow">{{ $isEditingSalesChannel ? 'تحديث قناة البيع' : 'تخصيص قناة بيع جديدة' }}</div>
                        <h5 class="modal-title mb-1">{{ $modalTitle }}</h5>
                        <p class="user-editor-subtitle mb-0">حدد قناة البيع (متجر، إلكتروني، جملة) لتتبع مصادر الإيرادات بدقة.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if ($isEditingSalesChannel)
                    @method('PUT')
                @endif
                <input type="hidden" name="sales_channel_modal" value="{{ $modalId }}">
                <div class="modal-body user-editor-body">
                    <div class="user-editor-overview">
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">الحالة</span>
                            <strong>{{ $salesChannelDefaultValue ? 'قناة افتراضية' : 'قناة إضافية' }}</strong>
                        </div>
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">المعرف</span>
                            <strong>{{ $salesChannelCodeValue ?: '-' }}</strong>
                        </div>
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">الاسم الحالي</span>
                            <strong>{{ $salesChannelNameValue ?: '-' }}</strong>
                        </div>
                        <div class="user-editor-overview-item">
                            <span class="user-editor-overview-label">الوضع</span>
                            <strong>{{ $isEditingSalesChannel ? 'تعديل نشط' : 'إنشاء جديد' }}</strong>
                        </div>
                    </div>

                    <div class="user-editor-panel-highlight p-4 rounded-4 bg-white border">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">اسم قناة البيع</label>
                                <input type="text" name="name" class="form-control" value="{{ $salesChannelNameValue }}" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="salesChannelDefault{{ $modalId }}" @checked($salesChannelDefaultValue)>
                                    <label class="form-check-label fw-bold ms-2" for="salesChannelDefault{{ $modalId }}">تعيين كقناة بيع افتراضية (تُقيد إليها المبيعات مالم يُحدد غير ذلك)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer user-editor-footer">
                    <button type="button" class="btn btn-light user-editor-cancel" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary user-editor-submit">{{ $isEditingSalesChannel ? 'حفظ التعديلات' : 'إضافة القناة' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
