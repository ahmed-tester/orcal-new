<x-filament-panels::page>
    {{-- Custom Page Styles & Print Rules --}}
    @include('filament.resources.payroll-records.pages.partials.styles')

    <div class="payroll-container">

        {{-- Top Navigation: Sheet View vs Records History Table & Print Triggers --}}
        @include('filament.resources.payroll-records.pages.partials.header-tabs')

        @php /* Records tab view — commented out
        @if($pageViewTab === 'records')
            {{-- Standard Filament Table --}}
            <div>
                {{ $this->table }}
            </div>
        @else
        */ @endphp
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

                {{-- Official Signatures Block (Displays only when printing) --}}
                @include('filament.resources.payroll-records.pages.partials.print-signatures')

            </div>
        @php /* @endif — commented out (was for records tab conditional) */ @endphp

    </div>
</x-filament-panels::page>
