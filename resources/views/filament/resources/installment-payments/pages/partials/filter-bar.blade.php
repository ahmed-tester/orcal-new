<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('finance.installment_payments.fields.company')"
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
                :label="__('finance.installment_payments.fields.status')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Enums\ApprovalStatus::class"
                :multiple="true"
                :searchable="true"
            />

            {{-- Payment Type --}}
            <x-filter-select
                wire:model.live="paymentType"
                :label="__('finance.installment_payments.fields.payment_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'installment' => __('finance.installment_payments.types.installment'),
                    'advance'     => __('finance.installment_payments.types.advance'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Payment Source --}}
            <x-filter-select
                wire:model.live="paymentSource"
                :label="__('finance.installment_payments.fields.payment_source')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Enums\InstallmentPaymentSource::class"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
