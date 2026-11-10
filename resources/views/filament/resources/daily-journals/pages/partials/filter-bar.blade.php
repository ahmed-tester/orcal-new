<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('finance.daily_journals.fields.company')"
        :multiple-company="true"
        :show-branch="true"
        :branches="$this->branches"
        :branch-label="__('finance.daily_journals.fields.branch')"
        :multiple-branch="true"
        :show-dates="true"
        :date-from-label="__('reports.filters.date_from')"
        :date-to-label="__('reports.filters.date_to')"
        :show-search="true"
        :search-placeholder="__('reports.filters.search_placeholder')"
        :has-active="$this->hasActiveFilters()"
        reset-method="resetFilters"
    >
        <x-slot name="append">
            {{-- Journal Type --}}
            <x-filter-select
                wire:model.live="isDetailed"
                :label="__('finance.daily_journals.fields.journal_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    '1' => __('finance.daily_journals.types.detailed'),
                    '0' => __('finance.daily_journals.types.summary'),
                ]"
                :multiple="false"
                :searchable="false"
            />
        </x-slot>
    </x-filter-bar>
</div>
