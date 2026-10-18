{{-- 1. Main Scope & Mode Filter Card --}}
<div class="payroll-card no-print">

    {{-- Header with Mode Title and Operation Type Pill --}}
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <div style="font-size: 1rem; font-weight: 800; color: #1e293b;">طريقة جلب وتحديد نطاق الكشف</div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">حدد أحد الخيارات المتاحة لتوليد كشف موظفي الرواتب أو التحصيل</div>
        </div>

        {{-- Operation Type Switch (رواتب / تحصيل) --}}
        <div class="op-switch">
            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; padding: 0 0.4rem;">العملية:</span>
            <button
                type="button"
                wire:click="setOperationType('payroll')"
                class="op-btn {{ $operationType === 'payroll' ? 'op-btn-payroll' : 'op-btn-inactive' }}"
            >
                <x-heroicon-s-banknotes style="width: 1rem; height: 1rem;" />
                <span>رواتب 💵</span>
            </button>
            <button
                type="button"
                wire:click="setOperationType('collection')"
                class="op-btn {{ $operationType === 'collection' ? 'op-btn-collection' : 'op-btn-inactive' }}"
            >
                <x-heroicon-s-arrow-down-tray style="width: 1rem; height: 1rem;" />
                <span>تحصيل 📥</span>
            </button>
        </div>
    </div>

    {{-- The 4 Mode Options --}}
    <div class="modes-grid">
        {{-- Mode 1 --}}
        <div
            wire:click="$set('filterMode', 'single_company')"
            class="mode-box {{ $filterMode === 'single_company' ? 'mode-box-active' : 'mode-box-inactive' }}"
        >
            <x-heroicon-o-building-office-2 style="width: 1.4rem; height: 1.4rem;" />
            <div>
                <div style="font-size: 0.85rem; font-weight: 800;">الخيار الأول</div>
                <div style="font-size: 0.725rem; opacity: 0.85;">باسم منشأة محددة</div>
            </div>
        </div>

        {{-- Mode 2 --}}
        <div
            wire:click="$set('filterMode', 'multiple_companies')"
            class="mode-box {{ $filterMode === 'multiple_companies' ? 'mode-box-active' : 'mode-box-inactive' }}"
        >
            <x-heroicon-o-building-office style="width: 1.4rem; height: 1.4rem;" />
            <div>
                <div style="font-size: 0.85rem; font-weight: 800;">الخيار الثاني</div>
                <div style="font-size: 0.725rem; opacity: 0.85;">تحديد مؤسسات معينة</div>
            </div>
        </div>

        {{-- Mode 3 (اختيار الكل - كافة المنشآت) --}}
        @if(auth()->user()?->canAccessAllCompanies())
        <div
            wire:click="$set('filterMode', 'all_companies')"
            class="mode-box {{ $filterMode === 'all_companies' ? 'mode-box-active' : 'mode-box-inactive' }}"
        >
            <x-heroicon-o-globe-alt style="width: 1.4rem; height: 1.4rem;" />
            <div>
                <div style="font-size: 0.85rem; font-weight: 800;">الخيار الثالث</div>
                <div style="font-size: 0.725rem; opacity: 0.85;">اختيار الكل (كافة المنشآت)</div>
            </div>
        </div>
        @endif

        {{-- Mode 4 --}}
        <div
            wire:click="$set('filterMode', 'by_guarantor')"
            class="mode-box {{ $filterMode === 'by_guarantor' ? 'mode-box-active' : 'mode-box-inactive' }}"
        >
            <x-heroicon-o-shield-check style="width: 1.4rem; height: 1.4rem;" />
            <div>
                <div style="font-size: 0.85rem; font-weight: 800;">الخيار الرابع</div>
                <div style="font-size: 0.725rem; opacity: 0.85;">باسم الضامن / الكفيل</div>
            </div>
        </div>
    </div>

    {{-- Dynamic Sub-controls Based on Active Mode --}}
    <div style="padding-top: 0.75rem; border-top: 1px solid #f1f5f9;">
        @if($filterMode === 'single_company')
            <div class="form-row">
                <div>
                    <label class="form-control-label">🏢 اسم المنشأة / المؤسسة</label>
                    <select wire:model.live="selectedCompanyId" class="form-select">
                        @foreach($this->companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->trade_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-control-label">👥 نطاق موظفي المنشأة</label>
                    <div style="display: flex; gap: 0.4rem;">
                        <button
                            type="button"
                            wire:click="$set('employeeScope', 'all')"
                            class="btn-action {{ $employeeScope === 'all' ? 'btn-primary' : 'btn-secondary' }}"
                            style="flex: 1; justify-content: center;"
                        >
                            اختيار الكل (الجميع)
                        </button>
                        <button
                            type="button"
                            wire:click="$set('employeeScope', 'specific')"
                            class="btn-action {{ $employeeScope === 'specific' ? 'btn-primary' : 'btn-secondary' }}"
                            style="flex: 1; justify-content: center;"
                        >
                            موظف معين
                        </button>
                    </div>
                </div>

                @if($employeeScope === 'specific')
                    <div>
                        <label class="form-control-label">👤 اختر موظف من المؤسسة</label>
                        <select wire:model.live="selectedEmployeeId" class="form-select">
                            <option value="">-- اختر الموظف --</option>
                            @foreach($this->companyEmployees as $cEmp)
                                <option value="{{ $cEmp->id }}">{{ $cEmp->user?->name ?? ('#' . $cEmp->id) }} ({{ $cEmp->employee_number }})</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div style="font-size: 0.75rem; color: #64748b; padding: 0.6rem 0;">
                        ℹ️ سيتم جلب كامل موظفي المنشأة المختارة.
                    </div>
                @endif
            </div>

        @elseif($filterMode === 'multiple_companies')
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                    <label class="form-control-label" style="margin-bottom: 0;">
                        🏬 حدد المؤسسات المطلوب إدراج موظفيها في الكشف:
                    </label>
                    <div style="display: flex; gap: 0.4rem; align-items: center;">
                        <button
                            type="button"
                            wire:click="selectAllCompanies"
                            class="btn-action btn-secondary"
                            style="padding: 0.25rem 0.65rem; font-size: 0.75rem;"
                        >
                            ✅ تحديد الكل
                        </button>
                        <button
                            type="button"
                            wire:click="deselectAllCompanies"
                            class="btn-action btn-secondary"
                            style="padding: 0.25rem 0.65rem; font-size: 0.75rem;"
                        >
                            ✕ إلغاء الكل
                        </button>
                    </div>
                </div>

                {{ $this->form }}
            </div>

        @elseif($filterMode === 'all_companies')
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 0.65rem 1rem; border-radius: 0.65rem; font-size: 0.8rem; font-weight: 600;">
                🌐 كشف شامل بكامل موظفي جميع المؤسسات والمنشآت المسجلة في النظام.
            </div>

        @elseif($filterMode === 'by_guarantor')
            <div class="form-row">
                <div style="flex: 1;">
                    <label class="form-control-label">🛡️ اختيار اسم الضامن / الكفيل</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <select wire:model.live="selectedGuarantorId" class="form-select" style="flex: 1;">
                            <option value="">-- اختر اسم الضامن --</option>
                            @foreach($this->guarantors as $guar)
                                <option value="{{ $guar->id }}">{{ $guar->name }}</option>
                            @endforeach
                        </select>
                        @if(!empty($selectedGuarantorId))
                            <button
                                type="button"
                                wire:click="clearGuarantor"
                                class="btn-action btn-secondary"
                                title="إلغاء التحديد"
                                style="padding: 0.45rem 0.75rem; white-space: nowrap;"
                            >
                                ✕ تفريغ
                            </button>
                        @endif
                    </div>
                </div>
                <div style="font-size: 0.75rem; color: #64748b; padding: 0.6rem 0; flex: 1;">
                    @if($this->guarantors->isEmpty())
                        <span style="color: #ea580c; font-weight: 700;">⚠️ لا يوجد ضامنون مسجلون في جدول الضامنين حالياً.</span>
                    @else
                        <span>ℹ️ يعرض الموظفين المكفولين تحت هذا الضامن (مسجل حالياً: {{ $this->guarantors->count() }} ضامن).</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Month, Date Range & Instant Search --}}
    <div class="form-row" style="padding-top: 0.75rem; border-top: 1px solid #f1f5f9;">
        <div>
            <label class="form-control-label">📅 شهر المسير</label>
            <input type="month" wire:model.live="payrollMonth" class="form-input" />
        </div>

        <div>
            <label class="form-control-label">📆 نطاق التاريخ</label>
            <div style="display: flex; gap: 0.4rem; align-items: center;">
                <input type="date" wire:model.live="dateFrom" class="form-input" style="flex: 1;" />
                <span style="font-size: 0.75rem; color: #94a3b8;">إلى</span>
                <input type="date" wire:model.live="dateTo" class="form-input" style="flex: 1;" />
            </div>
        </div>

        <div>
            <label class="form-control-label">🔍 بحث فوري</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                placeholder="ابحث باسم الموظف، الرقم الوظيفي، المنشأة..."
                class="form-input"
            />
        </div>
    </div>
</div>
