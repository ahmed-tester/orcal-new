<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('violations.violations.fields.company')"
        :multiple-company="true"
        :show-dates="true"
        :date-from-label="__('reports.filters.date_from')"
        :date-to-label="__('reports.filters.date_to')"
        :show-search="true"
        :search-placeholder="__('violations.violations.filters.search_placeholder')"
        :has-active="$this->hasActiveFilters()"
        reset-method="resetFilters"
    >
        <x-slot name="append">
            {{-- Category Filter --}}
            <x-filter-select
                wire:model.live="categoryId"
                :label="__('violations.violations.fields.category')"
                :placeholder="__('violations.violations.filters.all_categories')"
                :options="$this->categories"
                :multiple="true"
                :searchable="true"
            />

            {{-- Type Filter --}}
            <x-filter-select
                wire:model.live="typeId"
                :label="__('violations.violations.fields.type')"
                :placeholder="__('violations.violations.filters.all_types')"
                :options="$this->violationTypes"
                :multiple="true"
                :searchable="true"
            />

            {{-- Payment Status Filter --}}
            <x-filter-select
                wire:model.live="paymentStatus"
                :label="__('violations.violations.fields.payment_status')"
                :placeholder="__('violations.violations.filters.all_payment_statuses')"
                :options="\App\Enums\ViolationPaymentStatus::class"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
