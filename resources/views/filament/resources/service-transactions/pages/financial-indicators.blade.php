<x-filament-panels::page>
    <div class="service-financial-report" wire:init="applyFilters">
        <x-filament::section>
            <form wire:submit="applyFilters">
                <fieldset class="financial-report-grid" @disabled($groups === null) wire:loading.attr="disabled" wire:target="applyFilters">
                    <label>
                        {{ __('financial.indicators.company') }}
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="companyId">
                                <option value="">{{ __('financial.indicators.all') }}</option>
                                @foreach ($companies as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                    <label>
                        {{ __('financial.indicators.service') }}
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="serviceFilter">
                                <option value="">{{ __('financial.indicators.all') }}</option>
                                @foreach ($services as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                    <label>
                        {{ __('financial.indicators.date_from') }}
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model="dateFrom" />
                        </x-filament::input.wrapper>
                    </label>
                    <label>
                        {{ __('financial.indicators.date_to') }}
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model="dateTo" />
                        </x-filament::input.wrapper>
                    </label>
                    <div class="financial-report-apply">
                        <x-filament::button type="submit">{{ __('financial.indicators.apply') }}</x-filament::button>
                    </div>
                </fieldset>
            </form>
        </x-filament::section>

        <div role="status" @if ($groups !== null) wire:loading wire:target="applyFilters" @endif>
            {{ __('financial.indicators.loading') }}
        </div>

        @if ($groups !== null)
            <div class="financial-report-grid">
                @foreach (['received', 'custody', 'treasury_received', 'fee_paid', 'employee_due'] as $metric)
                    <x-filament::section>
                        <dl>
                            <dt>{{ __('financial.summary.'.$metric) }}</dt>
                            <dd><bdi dir="ltr">{{ number_format(array_sum(array_column(array_column($groups['company'], 'amounts'), $metric)), 2) }}</bdi></dd>
                        </dl>
                    </x-filament::section>
                @endforeach
            </div>
        @endif

        @include('filament.resources.service-transactions.financial-indicators')
    </div>
</x-filament-panels::page>
