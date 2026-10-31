<x-filament-panels::page>
    {{-- Custom Page Styles & Print Rules --}}
    @include('filament.resources.payroll-records.pages.partials.styles')

    <div class="payroll-container">

        {{-- Top Navigation: Sheet View vs Records History Table & Print Triggers --}}
        @include('filament.resources.payroll-records.pages.partials.header-tabs')

        @if($pageViewTab === 'records')
            {{-- Historical Archive View (Folder Files & Table) --}}
            @include('filament.resources.payroll-records.pages.partials.history-archive')
        @else
            {{-- Interactive Sheet View --}}
            <div id="printable-sheet" style="display: flex; flex-direction: column; gap: 1.25rem;">

                {{-- Formal Printable Header (Displays only when printing) --}}
                @include('filament.resources.payroll-records.pages.partials.print-header')

                {{-- 1. Main Scope & Mode Filter Card --}}
                @include('filament.resources.payroll-records.pages.partials.filters')

                {{-- 2. KPI Summary Cards --}}
                @include('filament.resources.payroll-records.pages.partials.kpi-cards')

                {{-- 3. Actions Toolbar --}}
                @include('filament.resources.payroll-records.pages.partials.toolbar')

                {{-- 4. Interactive Data Table --}}
                @include('filament.resources.payroll-records.pages.partials.sheet-table')

                {{-- 5. Pagination Bar (no-print) --}}
                @include('filament.resources.payroll-records.pages.partials.pagination')

            </div>
        @endif

    </div>
</x-filament-panels::page>
