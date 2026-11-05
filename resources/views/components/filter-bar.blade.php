@props([
    'title' => __('reports.filters.title'),
    'icon' => 'heroicon-o-funnel',
    'showSearch' => true,
    'searchPlaceholder' => __('reports.filters.search_placeholder'),
    'showDates' => false,
    'dateFromLabel' => __('reports.filters.date_from'),
    'dateToLabel' => __('reports.filters.date_to'),
    'showCompany' => false,
    'companyLabel' => __('organization.companies.single'),
    'companies' => [],
    'multipleCompany' => false,
    'showBranch' => false,
    'branchLabel' => __('reports.filters.branch'),
    'branches' => [],
    'multipleBranch' => false,
    'showStatus' => false,
    'statusLabel' => __('reports.filters.status'),
    'statusOptions' => [],
    'multipleStatus' => false,
    'searchableSelects' => true,
    'showPerPage' => false,
    'perPageLabel' => __('reports.filters.per_page'),
    'perPageOptions' => [10 => '10', 15 => '15', 25 => '25', 50 => '50', 100 => '100'],
    'showReset' => true,
    'resetMethod' => 'resetFilterBar',
    'hasActive' => false,
])

<style>
    .fb-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 1.15rem 1.25rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .dark .fb-card {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
    }

    .fb-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .dark .fb-card-header {
        border-bottom-color: #334155;
    }

    .fb-header-start {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .fb-header-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .dark .fb-header-icon-box {
        background: rgba(37, 99, 235, 0.2);
        color: #60a5fa;
        border-color: rgba(37, 99, 235, 0.4);
    }
    .fb-header-icon-box svg {
        width: 17px !important;
        height: 17px !important;
        flex-shrink: 0;
    }

    .fb-header-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .dark .fb-header-title {
        color: #f8fafc;
    }

    .fb-active-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.725rem;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .dark .fb-active-badge {
        background: rgba(37, 99, 235, 0.2);
        color: #93c5fd;
        border-color: rgba(37, 99, 235, 0.4);
    }
    .fb-badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        background: #2563eb;
    }

    .fb-header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .fb-reset-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.8rem;
        border-radius: 0.5rem;
        font-size: 0.775rem;
        font-weight: 700;
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
        flex-shrink: 0;
        line-height: 1;
    }
    .fb-reset-btn:hover {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #b91c1c;
    }
    .dark .fb-reset-btn {
        background: rgba(220, 38, 38, 0.15);
        border-color: rgba(220, 38, 38, 0.35);
        color: #f87171;
    }
    .dark .fb-reset-btn:hover {
        background: rgba(220, 38, 38, 0.25);
    }
    .fb-reset-btn svg {
        width: 14px !important;
        height: 14px !important;
        min-width: 14px !important;
        flex-shrink: 0;
    }

    .fb-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
        align-items: flex-end;
    }
    .fb-col {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 0;
    }
    .fb-col-sm {
        max-width: 130px;
    }
    .fb-label {
        font-size: 0.775rem;
        font-weight: 700;
        color: #475569;
        margin: 0;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dark .fb-label {
        color: #cbd5e1;
    }

    .fb-input {
        width: 100%;
        height: 38px;
        padding: 0.45rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        color: #0f172a;
        font-size: 0.825rem;
        font-weight: 500;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .dark .fb-input {
        background-color: #0f172a;
        border-color: #334155;
        color: #f8fafc;
    }
    .fb-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .fb-search-wrap {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin-top: 0.25rem;
    }
    .fb-search-inner {
        position: relative;
        width: 100%;
    }
    .fb-search-input {
        width: 100%;
        height: 40px;
        padding: 0.5rem 2.4rem;
        border-radius: 0.5rem;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        color: #0f172a;
        font-size: 0.85rem;
        outline: none;
        box-sizing: border-box;
        transition: all 0.15s;
    }
    .dark .fb-search-input {
        background-color: #0f172a;
        border-color: #334155;
        color: #f8fafc;
    }
    .fb-search-input:focus {
        background-color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .fb-search-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        inset-inline-start: 0.85rem;
        color: #94a3b8;
        pointer-events: none;
        width: 16px !important;
        height: 16px !important;
        flex-shrink: 0;
    }
    .fb-search-clear {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        inset-inline-end: 0.85rem;
        color: #94a3b8;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s;
    }
    .fb-search-clear:hover {
        color: #dc2626;
    }
    .fb-search-clear svg {
        width: 16px !important;
        height: 16px !important;
        flex-shrink: 0;
    }

    /* Filter Select Styles */
    .fb-select-wrapper { position: relative; width: 100%; }
    .fb-select-trigger { width: 100%; height: 38px; display: flex; align-items: center; justify-content: space-between; padding: 0.45rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.825rem; font-weight: 500; cursor: pointer; transition: all 0.15s; text-align: start; box-sizing: border-box; }
    .dark .fb-select-trigger { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
    .fb-select-trigger:focus, .fb-select-trigger.active { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); outline: none; }
    .fb-select-trigger.has-value { font-weight: 700; color: #2563eb; border-color: #93c5fd; background-color: #eff6ff; }
    .dark .fb-select-trigger.has-value { color: #60a5fa; background-color: rgba(37,99,235,0.15); border-color: rgba(37,99,235,0.4); }
    .fb-select-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: inherit; }
    .fb-select-arrow { width: 14px !important; height: 14px !important; color: #64748b; flex-shrink: 0; margin-inline-start: 0.4rem; }
    .fb-select-dropdown { position: absolute; top: calc(100% + 4px); inset-inline-start: 0; width: 100%; min-width: 220px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.625rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); z-index: 50; overflow: hidden; }
    .dark .fb-select-dropdown { background: #1e293b; border-color: #334155; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
    .fb-select-search-box { position: relative; padding: 0.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; }
    .dark .fb-select-search-box { border-bottom-color: #334155; }
    .fb-select-search-box .search-icon { position: absolute; inset-inline-start: 0.85rem; color: #94a3b8; pointer-events: none; width: 14px !important; height: 14px !important; }
    .fb-select-search-box .search-clear-btn { position: absolute; inset-inline-end: 0.85rem; color: #94a3b8; cursor: pointer; background: none; border: none; padding: 0; display: inline-flex; }
    .fb-select-search-box .search-clear-btn:hover { color: #dc2626; }
    .fb-select-search-input { width: 100%; height: 32px; padding: 0.35rem 2rem; border-radius: 0.375rem; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 0.8125rem; color: #0f172a; outline: none; box-sizing: border-box; }
    .dark .fb-select-search-input { background: #0f172a; border-color: #334155; color: #f8fafc; }
    .fb-select-bulk-actions { display: flex; align-items: center; justify-content: space-between; padding: 0.45rem 0.75rem; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .dark .fb-select-bulk-actions { background-color: #0f172a; border-bottom-color: #334155; }
    .fb-bulk-btn { font-size: 0.75rem; font-weight: 700; color: #2563eb; background: none; border: none; cursor: pointer; padding: 0.15rem 0.35rem; white-space: nowrap; transition: color 0.15s; }
    .fb-bulk-btn:hover { color: #1d4ed8; text-decoration: underline; }
    .fb-bulk-btn.text-danger { color: #dc2626; }
    .fb-bulk-btn.text-danger:hover { color: #b91c1c; }
    .fb-select-list { max-height: 220px; overflow-y: auto; list-style: none; margin: 0; padding: 0.25rem; }
    .fb-select-option { display: flex; align-items: center; justify-content: space-between; padding: 0.45rem 0.65rem; border-radius: 0.375rem; font-size: 0.8125rem; color: #334155; cursor: pointer; transition: background 0.1s; user-select: none; }
    .dark .fb-select-option { color: #cbd5e1; }
    .fb-select-option:hover { background: #eff6ff; color: #2563eb; font-weight: 600; }
    .dark .fb-select-option:hover { background: rgba(37,99,235,0.2); color: #60a5fa; }
    .fb-select-option.selected { background: #eff6ff; color: #2563eb; font-weight: 700; }
    .dark .fb-select-option.selected { background: rgba(37,99,235,0.25); color: #60a5fa; }
    .fb-select-check-icon { width: 14px !important; height: 14px !important; color: #2563eb; flex-shrink: 0; }
    .dark .fb-select-check-icon { color: #60a5fa; }
    .fb-checkbox { width: 15px; height: 15px; border-radius: 3px; border: 1.5px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; background: #fff; transition: all 0.1s; flex-shrink: 0; }
    .dark .fb-checkbox { background: #0f172a; border-color: #475569; }
    .fb-checkbox.checked { background: #2563eb; border-color: #2563eb; }
    .fb-select-empty { padding: 0.75rem; text-align: center; font-size: 0.75rem; color: #94a3b8; }
</style>

<div class="fb-card">
    {{-- Card Header --}}
    <div class="fb-card-header">
        <div class="fb-header-start">
            <div class="fb-header-icon-box">
                <x-dynamic-component :component="$icon" />
            </div>
            <h3 class="fb-header-title">{{ $title }}</h3>
            @if($hasActive)
                <span class="fb-active-badge">
                    <span class="fb-badge-dot"></span>
                    <span>{{ __('reports.filters_active') }}</span>
                </span>
            @endif
        </div>

        <div class="fb-header-actions">
            {{ $actions ?? '' }}

            @if($hasActive && $showReset)
                <button type="button" wire:click="{{ $resetMethod }}" class="fb-reset-btn" title="{{ __('reports.filters.reset') }}">
                    <x-heroicon-m-arrow-path />
                    <span>{{ __('reports.filters.reset') }}</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Fields Container --}}
    <div class="fb-grid">
        {{-- Slot for prepended custom controls (e.g. Report Type) --}}
        {{ $prepend ?? '' }}

        {{-- 1. Company Filter --}}
        @if($showCompany && count($companies ?? []) > 0)
            <x-filter-select
                wire:model.live="companyId"
                :label="$companyLabel"
                :placeholder="__('reports.filters.all_companies')"
                :options="$companies"
                :multiple="$multipleCompany"
                :searchable="$searchableSelects"
            />
        @endif

        {{-- 2. Branch Filter --}}
        @if($showBranch && count($branches ?? []) > 0)
            <x-filter-select
                wire:model.live="branchId"
                :label="$branchLabel"
                :placeholder="__('reports.filters.all_branches')"
                :options="$branches"
                :multiple="$multipleBranch"
                :searchable="$searchableSelects"
            />
        @endif

        {{-- 3. Status Filter --}}
        @if($showStatus && ! empty($statusOptions))
            <x-filter-select
                wire:model.live="status"
                :label="$statusLabel"
                :placeholder="__('reports.filters.status_all')"
                :options="$statusOptions"
                :multiple="$multipleStatus"
                :searchable="$searchableSelects"
            />
        @endif

        {{-- Middle custom slot --}}
        {{ $slot ?? '' }}

        {{-- 4. Date From --}}
        @if($showDates)
            <div class="fb-col">
                <label class="fb-label" title="{{ $dateFromLabel }}">{{ $dateFromLabel }}</label>
                <input type="date" wire:model.live="dateFrom" class="fb-input">
            </div>

            {{-- 5. Date To --}}
            <div class="fb-col">
                <label class="fb-label" title="{{ $dateToLabel }}">{{ $dateToLabel }}</label>
                <input type="date" wire:model.live="dateTo" class="fb-input">
            </div>
        @endif

        {{-- 6. Per Page --}}
        @if($showPerPage)
            <div class="fb-col fb-col-sm">
                <label class="fb-label" title="{{ $perPageLabel }}">{{ $perPageLabel }}</label>
                <select wire:model.live="perPage" class="fb-input">
                    @foreach($perPageOptions as $pVal => $pLabel)
                        <option value="{{ $pVal }}">{{ $pLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Appended custom slot --}}
        {{ $append ?? '' }}

        {{-- 7. Search Box Row --}}
        @if($showSearch)
            <div class="fb-search-wrap">
                <div class="fb-search-inner">
                    <x-heroicon-m-magnifying-glass class="fb-search-icon" />
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="search"
                        placeholder="{{ $searchPlaceholder }}"
                        class="fb-search-input"
                    >
                    @if(!empty($this->search))
                        <button type="button" wire:click="$set('search', '')" class="fb-search-clear">
                            <x-heroicon-m-x-mark />
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
