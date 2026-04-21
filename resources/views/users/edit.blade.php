@extends('layouts.app')

@section('title', 'تعديل مستخدم')

@php
    $groupTitles = [
        'team' => 'إدارة الفريق',
        'settings' => 'الإعدادات',
        'reports' => 'التقارير',
        'accounting' => 'المحاسبة',
        'sales' => 'المبيعات',
        'procurement' => 'المشتريات',
        'inventory' => 'المخزون',
        'hr' => 'الموارد البشرية',
    ];
    $assignedRoleIds = $user->roles->pluck('id')->all();
    $assignedPermissionIds = $user->permissions->pluck('id')->all();
@endphp

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="fas fa-user-edit"></i> تعديل مستخدم</h2>
        <p class="text-muted mt-2 mb-0">تعديل بيانات المستخدم: <strong>{{ $user->full_name }}</strong> وإدارة صلاحياته.</p>
    </div>
    <div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right ms-1"></i> العودة لقائمة المستخدمين
        </a>
    </div>
</div>

<form method="POST" action="{{ route('users.update', $user) }}">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-id-card ms-2 text-primary"></i> البيانات الأساسية</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">الاسم الأول <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">الاسم الأخير <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">اللغة</label>
                            <select name="language" class="form-select @error('language') is-invalid @enderror">
                                <option value="ar" @selected(old('language', $user->language) === 'ar')>العربية</option>
                                <option value="en" @selected(old('language', $user->language) === 'en')>English</option>
                            </select>
                            @error('language') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ربط بموظف</label>
                            <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                                <option value="">بدون ربط</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('employee_id', $user->employee_id) == $employee->id)>
                                        {{ $employee->full_name }}{{ $employee->branch?->name ? ' - ' . $employee->branch->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-lock ms-2 text-primary"></i> الأمان والوصول</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">كلمة مرور جديدة</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            <small class="text-muted">اترك الحقل فارغًا إذا لم تكن بحاجة إلى تغييرها الآن.</small>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                        <div class="col-12 mt-4">
                            <div class="form-check form-switch card p-3 border-light-subtle">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-check-label fw-bold" for="is_active">تنشيط الحساب</label>
                                        <p class="text-muted small mb-0">يسمح للمستخدم بالدخول واستخدام الشاشات المصرح بها.</p>
                                    </div>
                                    <input class="form-check-input ms-0" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $user->is_active))>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch card p-3 border-light-subtle">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-check-label fw-bold" for="must_change_password">إلزام تغيير كلمة المرور</label>
                                        <p class="text-muted small mb-0">سيُطلب من المستخدم تغيير كلمة المرور عند أول تسجيل دخول.</p>
                                    </div>
                                    <input class="form-check-input ms-0" type="checkbox" name="must_change_password" value="1" id="must_change_password" @checked(old('must_change_password', $user->must_change_password))>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 2rem;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user-tag ms-2 text-primary"></i> الأدوار</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">اختر أدوارًا تمنح حزمة صلاحيات متوقعة بسرعة.</p>
                    <div class="d-flex flex-column gap-3">
                        @foreach ($roles as $role)
                            <div class="role-selection-item">
                                <input type="checkbox" class="btn-check" name="role_ids[]" id="role_{{ $role->id }}" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $assignedRoleIds), true))>
                                <label class="btn btn-outline-light text-dark w-100 text-end p-3 border d-flex align-items-center" for="role_{{ $role->id }}">
                                    <div class="ms-3 flex-grow-1">
                                        <div class="fw-bold">{{ $role->display_name }}</div>
                                        <div class="small text-muted">{{ $role->description }}</div>
                                    </div>
                                    <i class="fas fa-check-circle check-icon text-primary opacity-0"></i>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('role_ids') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    
                    <div class="mt-4 pt-4 border-top">
                        <button type="submit" class="btn btn-primary w-100 py-3 mb-2">
                            <i class="fas fa-save ms-1"></i> حفظ التعديلات
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-light w-100 py-3">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-sliders ms-2 text-primary"></i> صلاحيات إضافية مباشرة</h5>
                    <span class="badge bg-light text-dark border">اختياري</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        @foreach ($permissions as $group => $groupPermissions)
                            <div class="col-md-6 col-xl-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">{{ $groupTitles[$group] ?? $group }}</h6>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($groupPermissions as $permission)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', $assignedPermissionIds), true))>
                                                <label class="form-check-label small" for="perm_{{ $permission->id }}">
                                                    <div class="fw-semibold">{{ $permission->display_name }}</div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $permission->description }}</div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
    .btn-check:checked + label {
        border-color: var(--bs-primary) !important;
        background-color: var(--bs-primary-bg-subtle) !important;
    }
    .btn-check:checked + label .check-icon {
        opacity: 1 !important;
    }
    .role-selection-item label {
        transition: all 0.2s ease;
    }
    .role-selection-item label:hover {
        border-color: var(--bs-primary-border-subtle);
    }
</style>
@endsection
