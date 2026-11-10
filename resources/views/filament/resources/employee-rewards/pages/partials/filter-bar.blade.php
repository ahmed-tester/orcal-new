<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('finance.employee_rewards.fields.company')"
        :multiple-company="true"
        :show-dates="true"
        :date-from-label="__('reports.filters.date_from')"
        :date-to-label="__('reports.filters.date_to')"
        :show-search="true"
        :search-placeholder="__('reports.filters.search_placeholder')"
        :has-active="$this->hasActiveFilters()"
        reset-method="resetFilters"
    >
        <x-slot name="append">
            {{-- Status --}}
            <x-filter-select
                wire:model.live="status"
                :label="__('finance.employee_rewards.fields.status')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Enums\RewardStatus::class"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>

