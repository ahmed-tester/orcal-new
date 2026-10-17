<x-filament-panels::page>
    <style>
        .report-page-container { display: flex; flex-direction: column; gap: 1.25rem; }
        .report-header-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .report-header-info { display: flex; align-items: center; gap: 0.875rem; }
        .report-header-icon { width: 48px; height: 48px; border-radius: 0.75rem; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bfdbfe; box-shadow: 0 1px 2px rgba(37,99,235,0.1); }
        .dark .report-header-icon { background: rgba(37, 99, 235, 0.2); color: #60a5fa; border-color: rgba(37, 99, 235, 0.4); }
        .report-header-title { font-size: 1.5rem; font-weight: 800; line-height: 1.2; color: #0f172a; }
        .dark .report-header-title { color: #f8fafc; }
        .report-header-sub { font-size: 0.875rem; color: #475569; margin-top: 0.25rem; }
        .dark .report-header-sub { color: #94a3b8; }
        .report-header-actions { display: flex; align-items: center; gap: 0.625rem; }

        /* Stats Grid */
        .report-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
        .report-stat-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s ease; }
        .report-stat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08); }
        .dark .report-stat-card { background-color: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .report-stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
        .report-stat-title { font-size: 0.8125rem; font-weight: 700; color: #475569; }
        .dark .report-stat-title { color: #cbd5e1; }
        .report-stat-icon-wrapper { width: 38px; height: 38px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .report-stat-value { font-size: 1.875rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem; color: #0f172a; }
        .dark .report-stat-value { color: #f8fafc; }
        .report-stat-subtitle { font-size: 0.8125rem; font-weight: 600; display: flex; align-items: center; gap: 0.375rem; }

        /* Filters */
        .report-filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .report-filter-group { display: flex; flex-direction: column; gap: 0.375rem; }
        .report-filter-label { font-size: 0.8125rem; font-weight: 700; color: #1e293b; }
        .dark .report-filter-label { color: #e2e8f0; }
        .report-filter-input { width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.875rem; outline: none; box-sizing: border-box; transition: border-color 0.15s; }
        .dark .report-filter-input { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-filter-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }

        /* Custom Alpine Searchable Select */
        .report-custom-select-wrapper { position: relative; width: 100%; }
        .report-custom-select-trigger { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.875rem; cursor: pointer; transition: all 0.15s; text-align: start; box-sizing: border-box; }
        .dark .report-custom-select-trigger { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-custom-select-trigger:focus, .report-custom-select-trigger.active { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); outline: none; }
        .report-custom-select-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
        .report-custom-select-arrow { color: #64748b; flex-shrink: 0; margin-inline-start: 0.5rem; transition: transform 0.2s; }
        .report-custom-select-dropdown { position: absolute; top: calc(100% + 4px); inset-inline-start: 0; width: 100%; min-width: 220px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.625rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); z-index: 50; overflow: hidden; }
        .dark .report-custom-select-dropdown { background: #1e293b; border-color: #334155; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
        .report-custom-select-search-box { position: relative; padding: 0.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; }
        .dark .report-custom-select-search-box { border-bottom-color: #334155; }
        .report-custom-select-search-box .search-icon { position: absolute; inset-inline-start: 0.85rem; color: #94a3b8; pointer-events: none; }
        .report-custom-select-search-input { width: 100%; padding: 0.375rem 2rem; border-radius: 0.375rem; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 0.8125rem; color: #0f172a; outline: none; box-sizing: border-box; }
        .dark .report-custom-select-search-input { background: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-custom-select-list { max-height: 220px; overflow-y: auto; list-style: none; margin: 0; padding: 0.25rem; }
        .report-custom-select-option { display: flex; align-items: center; justify-content: space-between; padding: 0.45rem 0.65rem; border-radius: 0.375rem; font-size: 0.8125rem; color: #334155; cursor: pointer; transition: background 0.1s; user-select: none; }
        .dark .report-custom-select-option { color: #cbd5e1; }
        .report-custom-select-option:hover { background: #eff6ff; color: #2563eb; font-weight: 600; }
        .dark .report-custom-select-option:hover { background: rgba(37,99,235,0.2); color: #60a5fa; }
        .report-custom-select-option.selected { background: #eff6ff; color: #2563eb; font-weight: 700; }
        .dark .report-custom-select-option.selected { background: rgba(37,99,235,0.25); color: #60a5fa; }
        .report-custom-select-option .check-icon { color: #2563eb; }
        .dark .report-custom-select-option .check-icon { color: #60a5fa; }
        .report-custom-select-empty { padding: 0.75rem; text-align: center; font-size: 0.75rem; color: #94a3b8; }
        .report-search-wrapper { grid-column: 1 / -1; display: flex; flex-direction: column; gap: 0.375rem; }
        .report-search-inner { position: relative; width: 100%; }
        .report-search-input { width: 100%; padding: 0.5rem 2.25rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; background-color: #ffffff; color: #0f172a; font-size: 0.875rem; outline: none; box-sizing: border-box; }
        .dark .report-search-input { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .report-search-icon { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: 0.75rem; color: #64748b; pointer-events: none; }
        .report-search-clear { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-end: 0.75rem; color: #64748b; cursor: pointer; background: none; border: none; }
        .report-filters-footer { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; font-size: 0.8125rem; color: #475569; }
        .dark .report-filters-footer { border-color: #334155; color: #94a3b8; }

        /* Table Screen View */
        .report-table-container { width: 100%; overflow-x: auto; border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .report-table-container { border-color: #334155; background-color: #1e293b; }
        .report-table { width: 100%; text-align: start; border-collapse: collapse; font-size: 0.875rem; min-width: 750px; }
        .report-table thead tr { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark .report-table thead tr { background-color: #0f172a; border-color: #334155; }
        .report-table th { padding: 0.875rem 1rem; font-weight: 700; font-size: 0.8125rem; color: #334155; text-align: inherit; }
        .dark .report-table th { color: #cbd5e1; }
        .report-table th button { background: none; border: none; cursor: pointer; font-weight: inherit; font-size: inherit; color: inherit; display: inline-flex; align-items: center; gap: 0.375rem; padding: 0; }
        .report-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
        .dark .report-table tbody tr { border-bottom-color: #334155; }
        .report-table tbody tr:hover { background-color: #f8fafc; }
        .dark .report-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.03); }
        .report-table td { padding: 0.75rem 1rem; white-space: nowrap; color: #0f172a; }
        .dark .report-table td { color: #f1f5f9; }
        .report-table-pagination { padding: 1rem; border-top: 1px solid #e2e8f0; }
        .dark .report-table-pagination { border-top-color: #334155; }

        /* Icon strict sizing */
        svg { max-width: 100%; }
        .report-icon-xs { width: 14px !important; height: 14px !important; min-width: 14px !important; max-height: 14px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-sm { width: 16px !important; height: 16px !important; min-width: 16px !important; max-height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-md { width: 20px !important; height: 20px !important; min-width: 20px !important; max-height: 20px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        .report-icon-lg { width: 24px !important; height: 24px !important; min-width: 24px !important; max-height: 24px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
        nav svg, .pagination svg, [role="navigation"] svg { width: 1.25rem !important; height: 1.25rem !important; max-width: 1.25rem !important; max-height: 1.25rem !important; flex-shrink: 0 !important; display: inline-block !important; }

        /* =========================================
           HIGH-CONTRAST, CLEAN PRINT STYLES
           ========================================= */
        @media print {
            @page { 
                size: A4 landscape; 
                margin: 6mm 8mm; 
            }

            *, *::before, *::after {
                background: transparent !important;
                color: #000000 !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            html, body { 
                background: #ffffff !important; 
                background-color: #ffffff !important;
                color: #000000 !important; 
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                overflow: visible !important;
                font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Hide everything by default, then make only the print wrapper visible */
            body * {
                visibility: hidden !important;
            }

            .report-print-wrapper, .report-print-wrapper * {
                visibility: visible !important;
            }

            .no-print, .fi-topbar, .fi-sidebar, .fi-breadcrumbs, .fi-header,
            .fi-main-ctn > nav, .report-page-container { 
                display: none !important; 
            }

            .print-only {
                display: block !important;
            }

            /* Suppress any link URLs */
            a[href]:after, a:after { 
                content: none !important; 
            }

            /* Print Report Header & Container pinned to top */
            .report-print-wrapper {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                box-sizing: border-box !important;
                direction: rtl !important;
            }

            .print-header {
                margin-bottom: 8px;
                padding-bottom: 6px;
                border-bottom: 2px solid #000000;
            }

            .print-header-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 4px;
            }

            .print-report-title {
                font-size: 15px !important;
                font-weight: 800 !important;
                margin: 0 0 2px 0 !important;
                color: #000000 !important;
            }

            .print-meta-sub {
                font-size: 10px !important;
                color: #333333 !important;
            }

            .print-date-badge {
                text-align: left !important;
                font-size: 9.5px !important;
                color: #111111 !important;
                line-height: 1.3;
            }

            .print-filters-summary {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin-top: 4px;
                font-size: 9px !important;
            }

            .print-filter-tag {
                border: 1px solid #999999;
                padding: 1px 5px;
                border-radius: 3px;
                background-color: #f8f8f8 !important;
                color: #111111 !important;
            }

            /* Print Table */
            .report-print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9.5px !important;
                color: #000000 !important;
                margin-top: 6px;
                table-layout: auto !important;
            }

            .report-print-table thead {
                display: table-header-group !important;
            }

            .report-print-table thead tr {
                background-color: #f0f0f0 !important;
            }

            .report-print-table th {
                border: 1px solid #000000 !important;
                padding: 4px 5px !important;
                font-weight: 700 !important;
                text-align: right !important;
                color: #000000 !important;
                font-size: 9.5px !important;
                line-height: 1.25 !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
            }

            .report-print-table td {
                border: 1px solid #666666 !important;
                padding: 3px 5px !important;
                color: #000000 !important;
                font-size: 9px !important;
                line-height: 1.25 !important;
                vertical-align: middle !important;
                text-align: right !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
            }

            .report-print-table tbody tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .report-print-table tbody tr:nth-child(even) {
                background-color: #fafafa !important;
            }

            .print-footer {
                margin-top: 8px;
                padding-top: 4px;
                border-top: 1px solid #cccccc;
                display: flex;
                justify-content: space-between;
                font-size: 8.5px !important;
                color: #555555 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    {{-- ==========================================
         DEDICATED FULL PRINTABLE REPORT (ALL ROWS)
         ========================================== --}}
    <div class="report-print-wrapper print-only">
        <div class="print-header">
            <div class="print-header-top">
                <div class="print-title-area">
                    <h1 class="print-report-title">{{ $this->currentReportInfo['label'] ?? __('reports.navigation.title') }}</h1>
                    <div class="print-meta-sub">{{ config('app.name', 'Orcal') }} — {{ __('reports.navigation.group') }}</div>
                </div>
                <div class="print-date-badge">
                    <div><strong>{{ __('reports.printed_at') }}:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                    <div><strong>{{ __('reports.total_records') }}:</strong> {{ count($this->rawReportData) }}</div>
                </div>
            </div>

            @if($dateFrom || $dateTo || $branchId || $status !== 'all' || $search)
                <div class="print-filters-summary">
                    @if($dateFrom || $dateTo)
                        <span class="print-filter-tag"><strong>{{ __('reports.period') }}:</strong> {{ $dateFrom ?? '...' }} {{ __('reports.to') }} {{ $dateTo ?? '...' }}</span>
                    @endif
                    @if($branchId)
                        @php $currentBranch = $this->branches->firstWhere('id', $branchId); @endphp
                        @if($currentBranch)
                            <span class="print-filter-tag"><strong>{{ __('reports.filters.branch') }}:</strong> {{ $currentBranch->name }}</span>
                        @endif
                    @endif
                    @if($guarantorId)
                        @php $currentGuarantor = $this->guarantors->firstWhere('id', $guarantorId); @endphp
                        @if($currentGuarantor)
                            <span class="print-filter-tag"><strong>{{ __('guarantors.single') }}:</strong> {{ $currentGuarantor->name }}</span>
                        @endif
                    @endif
                    @if($status !== 'all')
                        <span class="print-filter-tag"><strong>{{ __('reports.filters.status') }}:</strong> {{ __('reports.status.' . $status) ?? $status }}</span>
                    @endif
                    @if($search)
                        <span class="print-filter-tag"><strong>{{ __('reports.filters.search') }}:</strong> {{ $search }}</span>
                    @endif
                </div>
            @endif
        </div>

        <table class="report-print-table">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">#</th>
                    @foreach($this->columns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($this->rawReportData as $index => $item)
                    @php $row = \App\Services\Reports\GeneralReportService::formatRow($this->reportType, $item, true); @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700;">{{ $index + 1 }}</td>
                        @foreach($this->columns as $column)
                            <td>
                                @if(str_contains($column['key'], 'price') || str_contains($column['key'], 'amount') || str_contains($column['key'], 'salary') || str_contains($column['key'], 'total'))
                                    <span style="font-family: monospace; font-weight: 600;">{{ $row[$column['key']] ?? '—' }}</span>
                                @else
                                    {{ $row[$column['key']] ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($this->columns) + 1 }}" style="text-align: center; padding: 20px;">
                            {{ __('reports.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="print-footer">
            <span>{{ __('reports.print.footer_generated') }}</span>
            <span>{{ __('reports.print.footer_summary', ['count' => count($this->rawReportData)]) }}</span>
        </div>
    </div>
    
    {{-- ==========================================
         INTERACTIVE SCREEN INTERFACE (LIVEWIRE)
         ========================================== --}}
    <div class="report-page-container no-print">
        {{-- Top Action Bar --}}
        <div class="report-header-bar">
            <div class="report-header-info">
                <div class="report-header-icon">
                    <x-dynamic-component :component="$this->currentReportInfo['icon'] ?? 'heroicon-o-document-chart-bar'" class="report-icon-lg" />
                </div>
                <div>
                    <h2 class="report-header-title">
                        {{ $this->currentReportInfo['label'] ?? __('reports.navigation.title') }}
                    </h2>
                    <p class="report-header-sub">
                        {{ __('reports.total_records') }}: <strong style="color: #2563eb;">{{ $this->reportData->total() }}</strong>
                    </p>
                </div>
            </div>

            <div class="report-header-actions">
                <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray" color="primary" size="sm">
                    {{ __('reports.actions.export_excel') }}
                </x-filament::button>
                <x-filament::button color="gray" onclick="window.print()" icon="heroicon-o-printer" size="sm">
                    {{ __('reports.actions.print_pdf') }}
                </x-filament::button>
            </div>
        </div>

        {{-- Dynamic Stats Cards --}}
        @if(count($this->stats) > 0)
            <div class="report-stats-grid">
                @foreach($this->stats as $stat)
                    @php
                        $colorStyles = match($stat['color'] ?? 'primary') {
                            'green', 'success', 'emerald' => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0'],
                            'blue', 'primary', 'sky' => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
                            'amber', 'warning', 'yellow' => ['bg' => '#fffbeb', 'text' => '#d97706', 'border' => '#fde68a'],
                            'red', 'danger' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
                            'indigo', 'purple' => ['bg' => '#eef2ff', 'text' => '#4f46e5', 'border' => '#c7d2fe'],
                            default => ['bg' => '#f8fafc', 'text' => '#334155', 'border' => '#e2e8f0']
                        };
                    @endphp
                    <div class="report-stat-card">
                        <div class="report-stat-top">
                            <span class="report-stat-title">{{ $stat['title'] }}</span>
                            @if(isset($stat['icon']))
                                <div class="report-stat-icon-wrapper" style="background-color: {{ $colorStyles['bg'] }}; color: {{ $colorStyles['text'] }}; border: 1px solid {{ $colorStyles['border'] }};">
                                    <x-dynamic-component :component="$stat['icon']" class="report-icon-md" />
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="report-stat-value">
                                {{ is_numeric($stat['value']) ? number_format($stat['value']) : $stat['value'] }}
                            </div>
                            @if(isset($stat['subtitle']))
                                <div class="report-stat-subtitle" style="color: {{ $colorStyles['text'] }};">
                                    <span>{{ $stat['subtitle'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Filters Section --}}
        <x-filament::section icon="heroicon-o-funnel">
            <x-slot name="heading">
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <span>{{ __('reports.filters.title') }}</span>
                    @if($this->hasActiveFilters())
                        <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                            {{ __('reports.filters_active') }}
                        </span>
                    @endif
                </div>
            </x-slot>

            <div class="report-filters-grid">
                {{-- Report Type Searchable Select --}}
                <div class="report-filter-group">
                    <label class="report-filter-label">{{ __('reports.filters.report_type') }}</label>
                    <div 
                        x-data="{
                            open: false,
                            search: '',
                            value: @entangle('reportType').live,
                            options: {{ json_encode(collect($this->availableReports)->map(fn($r, $k) => ['value' => (string)$k, 'label' => $r['title']])->values()->toArray()) }},
                            get selectedLabel() {
                                let found = this.options.find(o => String(o.value) === String(this.value));
                                return found ? found.label : '{{ __('reports.filters.report_type') }}';
                            },
                            get filteredOptions() {
                                if (!this.search.trim()) return this.options;
                                let q = this.normalize(this.search.toLowerCase());
                                return this.options.filter(o => this.normalize(o.label.toLowerCase()).includes(q));
                            },
                            normalize(str) {
                                return (str || '')
                                    .replace(/[إأآا]/g, 'ا')
                                    .replace(/ة/g, 'ه')
                                    .replace(/ى/g, 'ي')
                                    .trim();
                            },
                            select(val) {
                                this.value = val;
                                this.open = false;
                                this.search = '';
                            }
                        }"
                        class="report-custom-select-wrapper"
                        @click.outside="open = false"
                    >
                        <button 
                            type="button" 
                            @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" 
                            class="report-custom-select-trigger"
                            :class="{ 'active': open }"
                        >
                            <span class="report-custom-select-label" x-text="selectedLabel"></span>
                            <x-heroicon-m-chevron-up-down class="report-icon-sm report-custom-select-arrow" />
                        </button>

                        <div 
                            x-show="open" 
                            x-transition
                            class="report-custom-select-dropdown"
                            x-cloak
                        >
                            <div class="report-custom-select-search-box">
                                <x-heroicon-m-magnifying-glass class="report-icon-xs search-icon" />
                                <input 
                                    type="text" 
                                    x-ref="searchInput" 
                                    x-model="search" 
                                    placeholder="{{ __('reports.filters.search_placeholder') }}" 
                                    class="report-custom-select-search-input"
                                    @keydown.escape="open = false"
                                />
                            </div>
                            <ul class="report-custom-select-list">
                                <template x-for="opt in filteredOptions" :key="opt.value">
                                    <li 
                                        @click="select(opt.value)" 
                                        class="report-custom-select-option"
                                        :class="{ 'selected': String(opt.value) === String(value) }"
                                    >
                                        <span x-text="opt.label"></span>
                                        <template x-if="String(opt.value) === String(value)">
                                            <x-heroicon-m-check class="report-icon-xs check-icon" />
                                        </template>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="report-custom-select-empty">
                                    {{ __('reports.sidebar.no_results') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Branch Searchable Select --}}
                <div class="report-filter-group">
                    <label class="report-filter-label">{{ __('reports.filters.branch') }}</label>
                    <div 
                        x-data="{
                            open: false,
                            search: '',
                            value: @entangle('branchId').live,
                            options: {{ json_encode(array_merge([['value' => '', 'label' => __('reports.filters.all_branches')]], $this->branches->map(fn($b) => ['value' => (string)$b->id, 'label' => $b->name])->toArray())) }},
                            get selectedLabel() {
                                let found = this.options.find(o => String(o.value) === String(this.value ?? ''));
                                return found ? found.label : '{{ __('reports.filters.all_branches') }}';
                            },
                            get filteredOptions() {
                                if (!this.search.trim()) return this.options;
                                let q = this.normalize(this.search.toLowerCase());
                                return this.options.filter(o => this.normalize(o.label.toLowerCase()).includes(q));
                            },
                            normalize(str) {
                                return (str || '')
                                    .replace(/[إأآا]/g, 'ا')
                                    .replace(/ة/g, 'ه')
                                    .replace(/ى/g, 'ي')
                                    .trim();
                            },
                            select(val) {
                                this.value = val === '' ? null : val;
                                this.open = false;
                                this.search = '';
                            }
                        }"
                        class="report-custom-select-wrapper"
                        @click.outside="open = false"
                    >
                        <button 
                            type="button" 
                            @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" 
                            class="report-custom-select-trigger"
                            :class="{ 'active': open }"
                        >
                            <span class="report-custom-select-label" x-text="selectedLabel"></span>
                            <x-heroicon-m-chevron-up-down class="report-icon-sm report-custom-select-arrow" />
                        </button>

                        <div 
                            x-show="open" 
                            x-transition
                            class="report-custom-select-dropdown"
                            x-cloak
                        >
                            <div class="report-custom-select-search-box">
                                <x-heroicon-m-magnifying-glass class="report-icon-xs search-icon" />
                                <input 
                                    type="text" 
                                    x-ref="searchInput" 
                                    x-model="search" 
                                    placeholder="{{ __('reports.filters.search_placeholder') }}" 
                                    class="report-custom-select-search-input"
                                    @keydown.escape="open = false"
                                />
                            </div>
                            <ul class="report-custom-select-list">
                                <template x-for="opt in filteredOptions" :key="opt.value">
                                    <li 
                                        @click="select(opt.value)" 
                                        class="report-custom-select-option"
                                        :class="{ 'selected': String(opt.value) === String(value ?? '') }"
                                    >
                                        <span x-text="opt.label"></span>
                                        <template x-if="String(opt.value) === String(value ?? '')">
                                            <x-heroicon-m-check class="report-icon-xs check-icon" />
                                        </template>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="report-custom-select-empty">
                                    {{ __('reports.sidebar.no_results') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                @if($this->reportType === 'guarantors')
                    {{-- Guarantor Searchable Select --}}
                    <div class="report-filter-group">
                        <label class="report-filter-label">{{ __('guarantors.single') }}</label>
                        <div 
                            x-data="{
                                open: false,
                                search: '',
                                value: @entangle('guarantorId').live,
                                options: {{ json_encode(array_merge([['value' => '', 'label' => __('guarantors.report.all_guarantors')]], $this->guarantors->map(fn($g) => ['value' => (string)$g->id, 'label' => $g->name])->toArray())) }},
                                get selectedLabel() {
                                    let found = this.options.find(o => String(o.value) === String(this.value ?? ''));
                                    return found ? found.label : '{{ __('guarantors.report.all_guarantors') }}';
                                },
                                get filteredOptions() {
                                    if (!this.search.trim()) return this.options;
                                    let q = this.normalize(this.search.toLowerCase());
                                    return this.options.filter(o => this.normalize(o.label.toLowerCase()).includes(q));
                                },
                                normalize(str) {
                                    return (str || '')
                                        .replace(/[إأآا]/g, 'ا')
                                        .replace(/ة/g, 'ه')
                                        .replace(/ى/g, 'ي')
                                        .trim();
                                },
                                select(val) {
                                    this.value = val === '' ? null : val;
                                    this.open = false;
                                    this.search = '';
                                }
                            }"
                            class="report-custom-select-wrapper"
                            @click.outside="open = false"
                        >
                            <button 
                                type="button" 
                                @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" 
                                class="report-custom-select-trigger"
                                :class="{ 'active': open }"
                            >
                                <span class="report-custom-select-label" x-text="selectedLabel"></span>
                                <x-heroicon-m-chevron-up-down class="report-icon-sm report-custom-select-arrow" />
                            </button>

                            <div 
                                x-show="open" 
                                x-transition
                                class="report-custom-select-dropdown"
                                x-cloak
                            >
                                <div class="report-custom-select-search-box">
                                    <x-heroicon-m-magnifying-glass class="report-icon-xs search-icon" />
                                    <input 
                                        type="text" 
                                        x-ref="searchInput" 
                                        x-model="search" 
                                        placeholder="{{ __('reports.filters.search_placeholder') }}" 
                                        class="report-custom-select-search-input"
                                        @keydown.escape="open = false"
                                    />
                                </div>
                                <ul class="report-custom-select-list">
                                    <template x-for="opt in filteredOptions" :key="opt.value">
                                        <li 
                                            @click="select(opt.value)" 
                                            class="report-custom-select-option"
                                            :class="{ 'selected': String(opt.value) === String(value ?? '') }"
                                        >
                                            <span x-text="opt.label"></span>
                                            <template x-if="String(opt.value) === String(value ?? '')">
                                                <x-heroicon-m-check class="report-icon-xs check-icon" />
                                            </template>
                                        </li>
                                    </template>
                                    <li x-show="filteredOptions.length === 0" class="report-custom-select-empty">
                                        {{ __('reports.sidebar.no_results') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Status Searchable Select --}}
                <div class="report-filter-group">
                    <label class="report-filter-label">{{ __('reports.filters.status') }}</label>
                    <div 
                        x-data="{
                            open: false,
                            search: '',
                            value: @entangle('status').live,
                            get options() {
                                return Object.entries($wire.statusOptions || {}).map(([val, label]) => ({ value: String(val), label: label }));
                            },
                            get selectedLabel() {
                                let found = this.options.find(o => String(o.value) === String(this.value));
                                return found ? found.label : '{{ __('reports.filters.status_all') }}';
                            },
                            get filteredOptions() {
                                if (!this.search.trim()) return this.options;
                                let q = this.normalize(this.search.toLowerCase());
                                return this.options.filter(o => this.normalize(o.label.toLowerCase()).includes(q));
                            },
                            normalize(str) {
                                return (str || '')
                                    .replace(/[إأآا]/g, 'ا')
                                    .replace(/ة/g, 'ه')
                                    .replace(/ى/g, 'ي')
                                    .trim();
                            },
                            select(val) {
                                this.value = val;
                                this.open = false;
                                this.search = '';
                            }
                        }"
                        class="report-custom-select-wrapper"
                        @click.outside="open = false"
                    >
                        <button 
                            type="button" 
                            @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" 
                            class="report-custom-select-trigger"
                            :class="{ 'active': open }"
                        >
                            <span class="report-custom-select-label" x-text="selectedLabel"></span>
                            <x-heroicon-m-chevron-up-down class="report-icon-sm report-custom-select-arrow" />
                        </button>

                        <div 
                            x-show="open" 
                            x-transition
                            class="report-custom-select-dropdown"
                            x-cloak
                        >
                            <div class="report-custom-select-search-box">
                                <x-heroicon-m-magnifying-glass class="report-icon-xs search-icon" />
                                <input 
                                    type="text" 
                                    x-ref="searchInput" 
                                    x-model="search" 
                                    placeholder="{{ __('reports.filters.search_placeholder') }}" 
                                    class="report-custom-select-search-input"
                                    @keydown.escape="open = false"
                                />
                            </div>
                            <ul class="report-custom-select-list">
                                <template x-for="opt in filteredOptions" :key="opt.value">
                                    <li 
                                        @click="select(opt.value)" 
                                        class="report-custom-select-option"
                                        :class="{ 'selected': String(opt.value) === String(value) }"
                                    >
                                        <span x-text="opt.label"></span>
                                        <template x-if="String(opt.value) === String(value)">
                                            <x-heroicon-m-check class="report-icon-xs check-icon" />
                                        </template>
                                    </li>
                                </template>
                                <li x-show="filteredOptions.length === 0" class="report-custom-select-empty">
                                    {{ __('reports.sidebar.no_results') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Per Page --}}
                <div class="report-filter-group">
                    <label class="report-filter-label">{{ __('reports.filters.per_page') }}</label>
                    <select wire:model.live="perPage" class="report-filter-input">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div class="report-filter-group">
                    <label class="report-filter-label">{{ __('reports.filters.date_from') }}</label>
                    <input type="date" wire:model.live="dateFrom" class="report-filter-input">
                </div>

                {{-- Date To --}}
                <div class="report-filter-group">
                    <label class="report-filter-label">{{ __('reports.filters.date_to') }}</label>
                    <input type="date" wire:model.live="dateTo" class="report-filter-input">
                </div>

                {{-- Search Bar --}}
                <div class="report-search-wrapper">
                    <label class="report-filter-label">{{ __('reports.filters.search') }}</label>
                    <div class="report-search-inner">
                        <div class="report-search-icon"><x-heroicon-m-magnifying-glass class="report-icon-sm" /></div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('reports.filters.search_placeholder') }}" class="report-search-input">
                        @if($search)
                            <button type="button" wire:click="$set('search', '')" class="report-search-clear"><x-heroicon-m-x-mark class="report-icon-sm" /></button>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Filter Actions --}}
            @if($this->hasActiveFilters())
                <div class="report-filters-footer">
                    <span>{{ __('reports.filters_active_hint') }}</span>
                    <x-filament::button wire:click="resetFilters" color="gray" size="xs" icon="heroicon-m-arrow-path">
                        {{ __('reports.filters.reset') }}
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>

        {{-- Interactive Data Table Section --}}
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        @foreach($this->columns as $column)
                            @php
                                $sortableMap = \App\Services\Reports\GeneralReportService::getSortableColumns($reportType);
                                $isSortable = array_key_exists($column['key'], $sortableMap);
                            @endphp
                            <th scope="col">
                                @if($isSortable)
                                    <button type="button" wire:click="sortBy('{{ $column['key'] }}')" style="{{ $sortColumn === $column['key'] ? 'color: #2563eb; font-weight: 800;' : '' }}">
                                        <span>{{ $column['label'] }}</span>
                                        @if($sortColumn === $column['key'])
                                            @if($sortDirection === 'asc')
                                                <x-heroicon-m-chevron-up class="report-icon-xs" style="color: #2563eb;" />
                                            @else
                                                <x-heroicon-m-chevron-down class="report-icon-xs" style="color: #2563eb;" />
                                            @endif
                                        @else
                                            <x-heroicon-m-chevron-up-down class="report-icon-xs" style="color: #94a3b8; opacity: 0.7;" />
                                        @endif
                                    </button>
                                @else
                                    <span>{{ $column['label'] }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->reportData as $item)
                        @php $row = \App\Services\Reports\GeneralReportService::formatRow($this->reportType, $item, false); @endphp
                        <tr>
                            @foreach($this->columns as $column)
                                <td>
                                    @if($column['key'] === 'status' || $column['key'] === 'payment_status' || $column['key'] === 'transaction_type' || $column['key'] === 'is_completed' || $column['key'] === 'priority' || $column['key'] === 'is_active')
                                        @php
                                            $val = $row[$column['key']] ?? '';
                                            $color = match($val) {
                                                __('reports.status.active'), __('reports.status.present'), __('reports.status.approved'), __('reports.status.paid'), __('reports.status.completed'), __('reports.status.accepted'), __('reports.status.incoming'), __('reports.values.yes'), __('reports.status.resolved'), __('reports.status.published') => 'success',
                                                __('reports.status.inactive'), __('reports.status.absent'), __('reports.status.rejected'), __('reports.status.unpaid'), __('reports.status.outgoing'), __('reports.values.no'), __('reports.status.closed'), __('reports.status.critical'), __('reports.status.urgent') => 'danger',
                                                __('reports.status.pending'), __('reports.status.late'), __('reports.status.partially_paid'), __('reports.status.partial'), __('reports.status.open'), __('reports.status.draft'), __('reports.status.sent'), __('reports.status.in_progress'), __('reports.status.medium') => 'warning',
                                                default => 'gray'
                                            };
                                        @endphp
                                        <x-filament::badge :color="$color">{{ $val }}</x-filament::badge>
                                    @elseif(str_contains($column['key'], 'price') || str_contains($column['key'], 'amount') || str_contains($column['key'], 'salary') || str_contains($column['key'], 'total'))
                                        <span style="font-weight: 700; font-family: monospace; color: #0f172a;">{{ $row[$column['key']] ?? '—' }}</span>
                                    @else
                                        {{ $row[$column['key']] ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->columns) }}" style="padding: 3.5rem 1.5rem; text-align: center; color: #64748b;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div style="width: 52px; height: 52px; border-radius: 9999px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin-bottom: 0.875rem;">
                                        <x-heroicon-o-document-magnifying-glass class="report-icon-lg" />
                                    </div>
                                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">{{ __('reports.empty') }}</h3>
                                    <p style="font-size: 0.8125rem; color: #64748b; max-width: 24rem;">{{ __('reports.empty_hint') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination Controls --}}
            @if($this->reportData->hasPages())
                <div class="report-table-pagination">
                    <x-filament::pagination :paginator="$this->reportData" :page-options="[10, 25, 50, 100, 250, 500]" current-page-option-property="perPage" />
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
