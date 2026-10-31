{{-- Historical Archive View (Year -> Month File Cards) --}}
<div class="archive-card no-print">

    {{-- 1. Archive Top Bar: Fiscal Year Selection & View Switchers --}}
    <div class="archive-header-bar">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <div>
                <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;" class="dark:text-white">
                    {{ __('finance.payroll_records.sheet.archive_title') }}
                </div>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">
                    {{ __('finance.payroll_records.sheet.archive_subtitle') }}
                </div>
            </div>

            {{-- Fiscal Year Selector Controls --}}
            <div style="display: flex; align-items: center; gap: 0.4rem; margin-right: 0.5rem; flex-wrap: wrap;">
                <span style="font-size: 0.75rem; font-weight: 700; color: #64748b;">{{ __('finance.payroll_records.sheet.archive_year_label') }}</span>
                
                <div class="archive-year-nav">
                    {{-- Previous Year Button --}}
                    <button
                        type="button"
                        wire:click="setArchiveYear({{ $archiveYear - 1 }})"
                        class="archive-year-btn archive-year-btn-inactive"
                        title="السنة السابقة ({{ $archiveYear - 1 }})"
                        style="padding: 0.4rem 0.55rem;"
                    >
                        <x-heroicon-m-chevron-right style="width: 1rem; height: 1rem;" />
                    </button>

                    {{-- Quick Year Pills --}}
                    @php
                        $sortedYears = $this->archiveYears;
                        sort($sortedYears);
                    @endphp
                    @foreach($sortedYears as $year)
                        <button
                            type="button"
                            wire:click="setArchiveYear({{ $year }})"
                            class="archive-year-btn {{ $archiveYear === $year ? 'archive-year-btn-active' : 'archive-year-btn-inactive' }}"
                        >
                            <span>{{ $year }}</span>
                        </button>
                    @endforeach

                    {{-- Next Year Button --}}
                    <button
                        type="button"
                        wire:click="setArchiveYear({{ $archiveYear + 1 }})"
                        class="archive-year-btn archive-year-btn-inactive"
                        title="السنة التالية ({{ $archiveYear + 1 }})"
                        style="padding: 0.4rem 0.55rem;"
                    >
                        <x-heroicon-m-chevron-left style="width: 1rem; height: 1rem;" />
                    </button>
                </div>

                {{-- Full Year Jump Dropdown --}}
                <select
                    wire:model.live="archiveYear"
                    style="padding: 0.4rem 0.65rem; font-size: 0.8rem; font-weight: 700; border-radius: 0.65rem; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; cursor: pointer;"
                    class="dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                    title="اختر أي سنة مالية"
                >
                    @for($y = max($this->archiveYears) + 2; $y >= min(min($this->archiveYears) - 2, 2020); $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
            {{-- Filter Operation Type within Archive --}}
            <div class="op-switch">
                <button
                    type="button"
                    wire:click="setArchiveOperationType('all')"
                    class="op-btn {{ $archiveOperationType === 'all' ? 'op-btn-payroll' : 'op-btn-inactive' }}"
                    style="font-size: 0.75rem; padding: 0.35rem 0.75rem;"
                >
                    <span>{{ __('finance.payroll_records.sheet.scope_all') }}</span>
                </button>
                <button
                    type="button"
                    wire:click="setArchiveOperationType('payroll')"
                    class="op-btn {{ $archiveOperationType === 'payroll' ? 'op-btn-payroll' : 'op-btn-inactive' }}"
                    style="font-size: 0.75rem; padding: 0.35rem 0.75rem;"
                >
                    <x-heroicon-s-banknotes style="width: 0.9rem; height: 0.9rem;" />
                    <span>{{ __('finance.payroll_records.sheet.badge_payroll') }}</span>
                </button>
                <button
                    type="button"
                    wire:click="setArchiveOperationType('collection')"
                    class="op-btn {{ $archiveOperationType === 'collection' ? 'op-btn-collection' : 'op-btn-inactive' }}"
                    style="font-size: 0.75rem; padding: 0.35rem 0.75rem;"
                >
                    <x-heroicon-s-arrow-down-tray style="width: 0.9rem; height: 0.9rem;" />
                    <span>{{ __('finance.payroll_records.sheet.badge_collection') }}</span>
                </button>
            </div>

            {{-- Toggle View Mode: Folders/Cards vs Flat Table --}}
            <div style="display: inline-flex; align-items: center; gap: 0.25rem; background: #f1f5f9; padding: 0.25rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;" class="dark:bg-slate-800 dark:border-slate-700">
                <button
                    type="button"
                    wire:click="toggleArchiveViewMode('folders')"
                    class="scope-pill-btn {{ $archiveViewMode === 'folders' ? 'scope-pill-btn-active' : 'scope-pill-btn-inactive' }}"
                    title="{{ __('finance.payroll_records.sheet.archive_view_folders') }}"
                >
                    <x-heroicon-o-folder style="width: 0.95rem; height: 0.95rem;" />
                    <span>{{ __('finance.payroll_records.sheet.archive_view_folders') }}</span>
                </button>
                <button
                    type="button"
                    wire:click="toggleArchiveViewMode('table')"
                    class="scope-pill-btn {{ $archiveViewMode === 'table' ? 'scope-pill-btn-active' : 'scope-pill-btn-inactive' }}"
                    title="{{ __('finance.payroll_records.sheet.archive_view_table') }}"
                >
                    <x-heroicon-o-table-cells style="width: 0.95rem; height: 0.95rem;" />
                    <span>{{ __('finance.payroll_records.sheet.archive_view_table') }}</span>
                </button>
            </div>
        </div>
    </div>

    @if($archiveViewMode === 'table')
        {{-- Raw Filament Table View --}}
        <div>
            {{ $this->table }}
        </div>
    @else
        {{-- Folders & Monthly Files View --}}
        <div style="display: flex; flex-direction: column; gap: 1.75rem;">

            {{-- Section 1: Payroll Sheets of the Year --}}
            @if($archiveOperationType === 'all' || $archiveOperationType === 'payroll')
                <div class="archive-section">
                    <div class="archive-section-title">
                        <x-heroicon-s-folder style="width: 1.35rem; height: 1.35rem; color: #2563eb;" />
                        <span>{{ __('finance.payroll_records.sheet.archive_payroll_heading', ['year' => $archiveYear]) }}</span>
                        <span style="font-size: 0.75rem; font-weight: 700; background: #eff6ff; color: #1d4ed8; padding: 0.1rem 0.55rem; border-radius: 9999px; border: 1px solid #bfdbfe;">
                            {{ count($this->archivedPayrollMonths) }} {{ __('finance.payroll_records.sheet.archive_view_folders') }}
                        </span>
                    </div>

                    @if(empty($this->archivedPayrollMonths))
                        <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 0.85rem; padding: 1.75rem; text-align: center;" class="dark:bg-slate-800 dark:border-slate-700">
                            <x-heroicon-o-folder-open style="width: 2.25rem; height: 2.25rem; color: #94a3b8; margin: 0 auto 0.5rem;" />
                            <div style="font-weight: 700; color: #475569;" class="dark:text-slate-300">
                                {{ __('finance.payroll_records.sheet.archive_empty_year', ['year' => $archiveYear]) }}
                            </div>
                            <div style="font-size: 0.775rem; color: #64748b; margin-top: 0.35rem;">
                                {{ __('finance.payroll_records.sheet.archive_start_month', ['year' => $archiveYear]) }}
                            </div>
                            <div style="display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap; margin-top: 0.85rem;">
                                @for($m = 1; $m <= 12; $m++)
                                    @php $paddedM = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                                    <button
                                        type="button"
                                        wire:click="openArchivedMonth('{{ $archiveYear }}-{{ $paddedM }}', 'payroll')"
                                        class="scope-pill-btn scope-pill-btn-inactive"
                                    >
                                        {{ \App\Services\PayrollSheetService::getArabicMonthName($m) }}
                                    </button>
                                @endfor
                            </div>
                        </div>
                    @else
                        <div class="archive-grid">
                            @foreach($this->archivedPayrollMonths as $month)
                                <div class="archive-file-card archive-file-card-payroll">
                                    <div class="archive-file-top">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="archive-file-icon-wrap archive-file-icon-payroll">
                                                <x-heroicon-s-folder style="width: 1.5rem; height: 1.5rem;" />
                                            </div>
                                            <div>
                                                <div class="archive-file-title">{{ $month['title'] }}</div>
                                                <div style="font-size: 0.725rem; color: #64748b; margin-top: 0.1rem; font-family: monospace;">
                                                    📅 {{ $month['month_key'] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            @if($month['is_fully_paid'])
                                                <span style="font-size: 0.68rem; font-weight: 800; background: #dcfce7; color: #15803d; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #bbf7d0;">
                                                    ✓ {{ __('finance.payroll_records.sheet.archive_fully_paid') }}
                                                </span>
                                            @elseif($month['paid_count'] > 0)
                                                <span style="font-size: 0.68rem; font-weight: 800; background: #fef3c7; color: #b45309; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #fde68a;">
                                                    ⏳ {{ __('finance.payroll_records.sheet.archive_partial_paid') }}
                                                </span>
                                            @else
                                                <span style="font-size: 0.68rem; font-weight: 800; background: #fee2e2; color: #b91c1c; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #fecaca;">
                                                    ● {{ __('finance.payroll_records.sheet.archive_unpaid') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="archive-file-stats">
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.kpi_employees_count') }}</span>
                                            <span class="archive-stat-val">{{ $month['total_employees'] }} {{ __('finance.payroll_records.sheet.employees_unit') }}</span>
                                        </div>
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.kpi_total_net') }}</span>
                                            <span class="archive-stat-val" style="color: #2563eb;">{{ number_format($month['total_net'], 2) }} <small style="font-size: 0.65rem;">ر.س</small></span>
                                        </div>
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.kpi_total_paid') }}</span>
                                            <span class="archive-stat-val" style="color: #15803d;">{{ number_format($month['total_paid'], 2) }}</span>
                                        </div>
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.archive_completion_rate') }}</span>
                                            <span class="archive-stat-val" style="color: {{ $month['completion_rate'] >= 100 ? '#15803d' : '#b45309' }};">
                                                {{ $month['completion_rate'] }}%
                                            </span>
                                        </div>
                                    </div>

                                    <div class="archive-file-actions">
                                        <button
                                            type="button"
                                            wire:click="openArchivedMonth('{{ $month['month_key'] }}', 'payroll')"
                                            class="btn-open-file"
                                            title="{{ __('finance.payroll_records.sheet.archive_open_sheet') }}"
                                        >
                                            <x-heroicon-s-arrow-top-right-on-square style="width: 1rem; height: 1rem;" />
                                            <span>{{ __('finance.payroll_records.sheet.archive_open_sheet') }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="printArchivedMonth('{{ $month['month_key'] }}', 'payroll', 'all')"
                                            class="btn-archive-print"
                                            title="{{ __('finance.payroll_records.sheet.print_all_btn') }}"
                                        >
                                            <x-heroicon-o-printer style="width: 1.05rem; height: 1.05rem;" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="printArchivedMonth('{{ $month['month_key'] }}', 'payroll', 'paid')"
                                            class="btn-archive-print"
                                            style="color: #059669;"
                                            title="{{ __('finance.payroll_records.sheet.print_paid_btn') }}"
                                        >
                                            <x-heroicon-o-check-badge style="width: 1.05rem; height: 1.05rem;" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="printArchivedMonth('{{ $month['month_key'] }}', 'payroll', 'unpaid')"
                                            class="btn-archive-print"
                                            style="color: #d97706;"
                                            title="{{ __('finance.payroll_records.sheet.print_unpaid_btn') }}"
                                        >
                                            <x-heroicon-o-clock style="width: 1.05rem; height: 1.05rem;" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Section 2: Collection Sheets of the Year --}}
            @if($archiveOperationType === 'all' || $archiveOperationType === 'collection')
                <div class="archive-section" style="border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                    <div class="archive-section-title">
                        <x-heroicon-s-folder style="width: 1.35rem; height: 1.35rem; color: #7c3aed;" />
                        <span>{{ __('finance.payroll_records.sheet.archive_collection_heading', ['year' => $archiveYear]) }}</span>
                        <span style="font-size: 0.75rem; font-weight: 700; background: #faf5ff; color: #7e22ce; padding: 0.1rem 0.55rem; border-radius: 9999px; border: 1px solid #e9d5ff;">
                            {{ count($this->archivedCollectionMonths) }} {{ __('finance.payroll_records.sheet.archive_view_folders') }}
                        </span>
                    </div>

                    @if(empty($this->archivedCollectionMonths))
                        <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 0.85rem; padding: 1.75rem; text-align: center;" class="dark:bg-slate-800 dark:border-slate-700">
                            <x-heroicon-o-folder-open style="width: 2.25rem; height: 2.25rem; color: #94a3b8; margin: 0 auto 0.5rem;" />
                            <div style="font-weight: 700; color: #475569;" class="dark:text-slate-300">
                                {{ __('finance.payroll_records.sheet.archive_empty_year', ['year' => $archiveYear]) }}
                            </div>
                            <div style="font-size: 0.775rem; color: #64748b; margin-top: 0.35rem;">
                                {{ __('finance.payroll_records.sheet.archive_start_month', ['year' => $archiveYear]) }}
                            </div>
                            <div style="display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap; margin-top: 0.85rem;">
                                @for($m = 1; $m <= 12; $m++)
                                    @php $paddedM = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                                    <button
                                        type="button"
                                        wire:click="openArchivedMonth('{{ $archiveYear }}-{{ $paddedM }}', 'collection')"
                                        class="scope-pill-btn scope-pill-btn-inactive"
                                    >
                                        {{ \App\Services\PayrollSheetService::getArabicMonthName($m) }}
                                    </button>
                                @endfor
                            </div>
                        </div>
                    @else
                        <div class="archive-grid">
                            @foreach($this->archivedCollectionMonths as $month)
                                <div class="archive-file-card archive-file-card-collection">
                                    <div class="archive-file-top">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="archive-file-icon-wrap archive-file-icon-collection">
                                                <x-heroicon-s-folder style="width: 1.5rem; height: 1.5rem;" />
                                            </div>
                                            <div>
                                                <div class="archive-file-title">{{ $month['title'] }}</div>
                                                <div style="font-size: 0.725rem; color: #64748b; margin-top: 0.1rem; font-family: monospace;">
                                                    📅 {{ $month['month_key'] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            @if($month['is_fully_paid'])
                                                <span style="font-size: 0.68rem; font-weight: 800; background: #dcfce7; color: #15803d; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #bbf7d0;">
                                                    ✓ {{ __('finance.payroll_records.sheet.archive_fully_paid') }}
                                                </span>
                                            @elseif($month['paid_count'] > 0)
                                                <span style="font-size: 0.68rem; font-weight: 800; background: #fef3c7; color: #b45309; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #fde68a;">
                                                    ⏳ {{ __('finance.payroll_records.sheet.archive_partial_paid') }}
                                                </span>
                                            @else
                                                <span style="font-size: 0.68rem; font-weight: 800; background: #fee2e2; color: #b91c1c; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #fecaca;">
                                                    ● {{ __('finance.payroll_records.sheet.archive_unpaid') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="archive-file-stats">
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.kpi_employees_count') }}</span>
                                            <span class="archive-stat-val">{{ $month['total_employees'] }} {{ __('finance.payroll_records.sheet.employees_unit') }}</span>
                                        </div>
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.kpi_total_net') }}</span>
                                            <span class="archive-stat-val" style="color: #7c3aed;">{{ number_format($month['total_net'], 2) }} <small style="font-size: 0.65rem;">ر.س</small></span>
                                        </div>
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.kpi_total_collected') }}</span>
                                            <span class="archive-stat-val" style="color: #15803d;">{{ number_format($month['total_paid'], 2) }}</span>
                                        </div>
                                        <div class="archive-stat-item">
                                            <span class="archive-stat-lbl">{{ __('finance.payroll_records.sheet.archive_completion_rate') }}</span>
                                            <span class="archive-stat-val" style="color: {{ $month['completion_rate'] >= 100 ? '#15803d' : '#b45309' }};">
                                                {{ $month['completion_rate'] }}%
                                            </span>
                                        </div>
                                    </div>

                                    <div class="archive-file-actions">
                                        <button
                                            type="button"
                                            wire:click="openArchivedMonth('{{ $month['month_key'] }}', 'collection')"
                                            class="btn-open-file"
                                            style="background: #7c3aed;"
                                            title="{{ __('finance.payroll_records.sheet.archive_open_sheet') }}"
                                        >
                                            <x-heroicon-s-arrow-top-right-on-square style="width: 1rem; height: 1rem;" />
                                            <span>{{ __('finance.payroll_records.sheet.archive_open_sheet') }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="printArchivedMonth('{{ $month['month_key'] }}', 'collection', 'all')"
                                            class="btn-archive-print"
                                            title="{{ __('finance.payroll_records.sheet.print_all_btn') }}"
                                        >
                                            <x-heroicon-o-printer style="width: 1.05rem; height: 1.05rem;" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="printArchivedMonth('{{ $month['month_key'] }}', 'collection', 'paid')"
                                            class="btn-archive-print"
                                            style="color: #059669;"
                                            title="{{ __('finance.payroll_records.sheet.print_paid_btn') }}"
                                        >
                                            <x-heroicon-o-check-badge style="width: 1.05rem; height: 1.05rem;" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="printArchivedMonth('{{ $month['month_key'] }}', 'collection', 'unpaid')"
                                            class="btn-archive-print"
                                            style="color: #d97706;"
                                            title="{{ __('finance.payroll_records.sheet.print_unpaid_btn') }}"
                                        >
                                            <x-heroicon-o-clock style="width: 1.05rem; height: 1.05rem;" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>
    @endif

</div>
