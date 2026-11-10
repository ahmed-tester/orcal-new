<div style="margin-bottom: 1.25rem;">
    <x-filter-bar
        :title="__('reports.filters.title')"
        :show-company="true"
        :companies="$this->companies"
        :company-label="__('services_tracking.fields.company')"
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
            {{-- Approval Status --}}
            <x-filter-select
                wire:model.live="status"
                :label="__('services_tracking.fields.status')"
                :placeholder="__('reports.filters.status_all')"
                :options="\App\Enums\ApprovalStatus::class"
                :multiple="true"
                :searchable="true"
            />

            {{-- Scope Type --}}
            <x-filter-select
                wire:model.live="scopeType"
                :label="__('services_tracking.fields.scope_type')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'internal' => __('services_tracking.toggle_options.internal'),
                    'external' => __('services_tracking.toggle_options.external'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Service Status --}}
            <x-filter-select
                wire:model.live="serviceStatus"
                :label="__('services_tracking.fields.service_status')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'active'    => __('services_tracking.service_statuses.active'),
                    'completed' => __('services_tracking.service_statuses.completed'),
                    'inactive'  => __('services_tracking.service_statuses.inactive'),
                ]"
                :multiple="true"
                :searchable="true"
            />

            {{-- Payment Status --}}
            <x-filter-select
                wire:model.live="paymentStatus"
                :label="__('services_tracking.fields.payment_status')"
                :placeholder="__('reports.filters.status_all')"
                :options="[
                    'unpaid'  => __('services_tracking.payment_statuses.unpaid'),
                    'partial' => __('services_tracking.payment_statuses.partial'),
                    'paid'    => __('services_tracking.payment_statuses.paid'),
                ]"
                :multiple="true"
                :searchable="true"
            />
        </x-slot>
    </x-filter-bar>
</div>
