<x-filament-panels::page>
    <div class="service-financial-report">
        <x-filter-bar
            :title="__('financial.indicators.title')"
            :show-company="true"
            :companies="$this->companies"
            :company-label="__('financial.indicators.company')"
            :show-dates="true"
            :date-from-label="__('financial.indicators.date_from')"
            :date-to-label="__('financial.indicators.date_to')"
            :show-search="false"
            :has-active="$this->hasActiveFilterBar()"
            reset-method="resetFilterBar"
        >
            <x-slot name="append">
                <x-filter-select
                    wire:model.live="serviceFilter"
                    :label="__('financial.indicators.service')"
                    :placeholder="__('financial.indicators.all')"
                    :options="$this->services"
                    :searchable="true"
                />
            </x-slot>
        </x-filter-bar>

        <div role="status" wire:loading wire:target="companyId, dateFrom, dateTo, serviceFilter, resetFilterBar">
            {{ __('financial.indicators.loading') }}
        </div>

        @if ($groups !== null)
            <div class="financial-report-grid">
                @foreach (['received', 'custody', 'treasury_received', 'fee_paid', 'employee_due'] as $metric)
                    <x-filament::section>
                        <dl>
                            <dt>{{ __('financial.summary.'.$metric) }}</dt>
                            <dd><bdi dir="ltr">{{ number_format(array_sum(array_column(array_column($groups['company'] ?? [], 'amounts'), $metric)), 2) }}</bdi></dd>
                        </dl>
                    </x-filament::section>
                @endforeach
            </div>
        @endif

        @include('filament.resources.service-transactions.financial-indicators')
    </div>
</x-filament-panels::page>
