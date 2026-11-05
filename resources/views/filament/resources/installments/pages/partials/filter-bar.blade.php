<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('finance.installments.fields.company')"
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
            {{-- Transaction Type --}}
            <x-filter-select
                wire:model.live="transactionType"
                :label="__('finance.installments.fields.transaction_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'installment' => __('finance.installments.toggle_options.installment'),
                    'advance'     => __('finance.installments.toggle_options.advance'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Beneficiary Type --}}
            <x-filter-select
                wire:model.live="beneficiaryType"
                :label="__('finance.installments.fields.beneficiary_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'internal' => __('finance.installments.sources.internal'),
                    'external' => __('finance.installments.sources.external'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Status --}}
            <x-filter-select
                wire:model.live="status"
                :label="__('finance.installments.fields.status')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Enums\InstallmentStatus::class"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
