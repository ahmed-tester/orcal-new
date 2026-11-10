<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('debts.fields.company')"
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
            {{-- Debtor Type --}}
            <x-filter-select
                wire:model.live="debtorType"
                :label="__('debts.fields.debtor_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'internal' => __('debts.toggle_options.internal'),
                    'external' => __('debts.toggle_options.external'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Status --}}
            <x-filter-select
                wire:model.live="status"
                :label="__('debts.fields.status')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'pending' => __('debts.statuses.pending'),
                    'partial' => __('debts.statuses.partial'),
                    'paid'    => __('debts.statuses.paid'),
                    'overdue' => __('debts.statuses.overdue'),
                ]"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
