<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('invoices.fields.company')"
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
            {{-- Invoice Type --}}
            <x-filter-select
                wire:model.live="invoiceTypeId"
                :label="__('invoices.fields.invoice_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Models\InvoiceType::orderBy('name')->pluck('name', 'id')->toArray()"
                :multiple="true"
                :searchable="true"
            />

            {{-- Payment Status --}}
            <x-filter-select
                wire:model.live="paymentStatus"
                :label="__('invoices.fields.payment_status')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Enums\PaymentStatus::class"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
