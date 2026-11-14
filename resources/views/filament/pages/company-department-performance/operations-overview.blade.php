@php
    $stats = $this->overallStats;
    $selectedCompany = $this->selectedCompany;
    $selectedDepartment = $this->selectedDepartment;

    /*
     * Decision per Handoff Plan: Top cards open kashf in general scope (passing null, null)
     * so numbers and statements match the overall filter period without narrowing prematurely.
     */
    $topCards = [
        [
            'title' => __('performance.cards.active_services'),
            'value' => number_format($stats->active_services),
            'hint' => __('performance.cards.active_services_hint'),
            'icon' => 'heroicon-o-play-circle',
            'accent' => '#2563eb',
            'action' => "openKashf('services', 'active', null, null)",
            'unit' => null,
        ],
        [
            'title' => __('performance.cards.completed_services'),
            'value' => number_format($stats->completed_services),
            'hint' => __('performance.cards.completed_services_hint'),
            'icon' => 'heroicon-o-check-circle',
            'accent' => '#059669',
            'action' => "openKashf('services', 'completed', null, null)",
            'unit' => null,
        ],
        [
            'title' => __('performance.cards.overdue_services'),
            'value' => number_format($stats->overdue_services),
            'hint' => __('performance.cards.overdue_services_hint'),
            'icon' => 'heroicon-o-exclamation-triangle',
            'accent' => '#e11d48',
            'action' => "openKashf('services', 'overdue', null, null)",
            'unit' => null,
        ],
        [
            'title' => __('performance.cards.on_time_rate'),
            'value' => $stats->on_time_rate !== null ? "{$stats->on_time_rate}%" : __('performance.cards.no_data'),
            'hint' => __('performance.cards.on_time_rate_hint'),
            'icon' => 'heroicon-o-clock',
            'accent' => '#7c3aed',
            'action' => "openKashf('services', 'on_time', null, null)",
            'unit' => null,
            'rate' => $stats->on_time_rate,
        ],
        [
            'title' => __('performance.cards.open_deficiencies'),
            'value' => number_format($stats->open_deficiencies),
            'hint' => __('performance.cards.open_deficiencies_hint'),
            'icon' => 'heroicon-o-exclamation-circle',
            'accent' => '#ea580c',
            'action' => "openKashf('deficiencies', 'all', null, null)",
            'unit' => null,
            'rate' => null,
        ],
        [
            'title' => __('performance.cards.tasks_completion_rate'),
            'value' => $stats->task_completion_rate !== null ? "{$stats->task_completion_rate}%" : __('performance.cards.no_data'),
            'hint' => __('performance.cards.tasks_completion_rate_hint'),
            'icon' => 'heroicon-o-clipboard-document-check',
            'accent' => '#0891b2',
            'action' => "openKashf('tasks', 'all', null, null)",
            'unit' => null,
            'rate' => $stats->task_completion_rate,
        ],
        [
            'title' => __('performance.columns.tasks_due'),
            'value' => number_format($stats->task_due_in_period),
            'hint' => __('performance.columns.view_statement'),
            'icon' => 'heroicon-o-calendar-days',
            'accent' => '#7c3aed',
            'action' => "openKashf('tasks', 'due')",
            'unit' => null,
        ],
        [
            'title' => __('performance.columns.tasks_completed'),
            'value' => number_format($stats->task_completed_in_period),
            'hint' => __('performance.columns.view_statement'),
            'icon' => 'heroicon-o-check-circle',
            'accent' => '#059669',
            'action' => "openKashf('tasks', 'completed')",
            'unit' => null,
        ],
    ];
@endphp

<h2 class="perf-section-title">{{ __('performance.overview.operations') }}</h2>
<div class="perf-notice-banner">
    <x-heroicon-o-information-circle style="width: 18px; height: 18px; flex-shrink: 0;" />
    <span>{{ __('performance.filters.branch_note') }}</span>
</div>
<div class="perf-kpi-grid">
    @foreach($topCards as $c)
        <x-performance.metric-card
            :title="$c['title']"
            :value="$c['value']"
            :hint="$c['hint']"
            :icon="$c['icon']"
            :accent="$c['accent']"
            :action="$c['action']"
            :unit="$c['unit']"
            :rate="$c['rate'] ?? null"
            :index="$loop->index"
        />
    @endforeach
</div>

<!-- Hierarchy Stepper / Breadcrumbs Bar -->
<div class="perf-stepper-bar">
    <div class="stepper-trail">
        @if($this->selectedCompanyId === null)
            <div class="stepper-step active">
                <x-heroicon-m-building-office-2 style="width: 18px; height: 18px; color: var(--a);" />
                <span>{{ __('performance.levels.companies') }}</span>
            </div>
        @elseif($this->selectedDepartmentId === null)
            <button class="stepper-step link" wire:click="backToCompanies" role="button">
                <x-heroicon-m-building-office-2 style="width: 18px; height: 18px;" />
                <span>{{ __('performance.levels.companies') }}</span>
            </button>
            <span class="stepper-divider">
                <x-heroicon-m-chevron-left style="width: 16px; height: 16px;" />
            </span>
            <div class="stepper-step active">
                <x-heroicon-m-rectangle-group style="width: 18px; height: 18px; color: var(--g);" />
                <span>{{ $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—') }}</span>
            </div>
        @else
            <button class="stepper-step link" wire:click="backToCompanies" role="button">
                <x-heroicon-m-building-office-2 style="width: 18px; height: 18px;" />
                <span>{{ __('performance.levels.companies') }}</span>
            </button>
            <span class="stepper-divider">
                <x-heroicon-m-chevron-left style="width: 16px; height: 16px;" />
            </span>
            <button class="stepper-step link" wire:click="backToDepartments" role="button">
                <x-heroicon-m-rectangle-group style="width: 18px; height: 18px;" />
                <span>{{ $selectedCompany?->trade_name ?: ($selectedCompany?->trade_name_en ?? '—') }}</span>
            </button>
            <span class="stepper-divider">
                <x-heroicon-m-chevron-left style="width: 16px; height: 16px;" />
            </span>
            <div class="stepper-step active">
                <x-heroicon-m-user-group style="width: 18px; height: 18px; color: var(--a);" />
                <span>
                    @if($this->selectedDepartmentId === 'unassigned')
                        {{ __('performance.levels.unassigned_or_no_department') }}
                    @else
                        {{ $selectedDepartment?->parent ? "{$selectedDepartment->name} ({$selectedDepartment->parent->name})" : $selectedDepartment?->name }}
                    @endif
                </span>
            </div>
        @endif
    </div>

    <div style="display: flex; align-items: center; gap: 0.65rem;">
        @if($this->selectedCompanyId !== null && $this->selectedDepartmentId === null)
            <x-filament::button color="gray" icon="heroicon-m-arrow-right" size="sm" wire:click="backToCompanies">
                {{ __('performance.levels.back_to_companies') }}
            </x-filament::button>
        @elseif($this->selectedDepartmentId !== null)
            <x-filament::button color="gray" icon="heroicon-m-arrow-right" size="sm" wire:click="backToDepartments">
                {{ __('performance.levels.back_to_departments') }}
            </x-filament::button>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- Active Hierarchy Level View -->
<!-- ========================================== -->
@if($this->selectedCompanyId === null)
    @include('filament.pages.company-department-performance.companies')
@elseif($this->selectedDepartmentId === null)
    @include('filament.pages.company-department-performance.departments')
@else
    @include('filament.pages.company-department-performance.employees')
@endif
