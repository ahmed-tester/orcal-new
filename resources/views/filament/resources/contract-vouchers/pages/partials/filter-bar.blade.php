<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('contract_vouchers.fields.company')"
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
            {{-- Voucher Type --}}
            <x-filter-select
                wire:model.live="voucherType"
                :label="__('contract_vouchers.fields.voucher_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'receipt' => __('contract_vouchers.types.receipt'),
                    'payment' => __('contract_vouchers.types.payment'),
                    'request' => __('contract_vouchers.types.request'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Payment Method --}}
            <x-filter-select
                wire:model.live="paymentMethod"
                :label="__('contract_vouchers.fields.payment_method')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'cash'          => __('contract_vouchers.payment_methods.cash'),
                    'check'         => __('contract_vouchers.payment_methods.check'),
                    'bank_transfer' => __('contract_vouchers.payment_methods.bank_transfer'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Status --}}
            <x-filter-select
                wire:model.live="status"
                :label="__('contract_vouchers.fields.status')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'draft'     => __('contract_vouchers.statuses.draft'),
                    'approved'  => __('contract_vouchers.statuses.approved'),
                    'cancelled' => __('contract_vouchers.statuses.cancelled'),
                ]"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
