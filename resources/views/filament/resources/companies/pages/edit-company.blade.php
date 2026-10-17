<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 1rem; display: flex; justify-content: flex-start; gap: 0.5rem;">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    {{-- سجل ملاك الشركة --}}
    <x-filament::section icon="heroicon-o-users">
        <x-slot name="heading">سجل ملاك الشركة</x-slot>

        <div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid rgba(0,0,0,0.08);">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem; text-align: right;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">رقم الوصل</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">اسم المالك</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">رقم الهوية</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">نسبة الملكية</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">بداية الملكية</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">الانقطاع</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap;">المدة</th>
                        <th style="padding: 0.6rem 0.75rem; font-weight: 600; color: #64748b; font-size: 0.75rem; white-space: nowrap; text-align: center;">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->companyOwners as $ownerItem)
                        @php
                            $voucherCode = 'O-' . str_pad($ownerItem->id, 4, '0', STR_PAD_LEFT);
                            $maskedId = $ownerItem->national_id
                                ? (strlen($ownerItem->national_id) > 4
                                    ? str_repeat('*', strlen($ownerItem->national_id) - 4) . substr($ownerItem->national_id, -4)
                                    : $ownerItem->national_id)
                                : '—';
                            $duration = \App\Filament\Resources\Companies\Pages\EditCompany::calculateDuration(
                                $ownerItem->employment_date?->toDateString(),
                                $ownerItem->termination_date?->toDateString()
                            );
                            $isInactive = !empty($ownerItem->termination_date) || $ownerItem->status === 'inactive' || $ownerItem->status === \App\Enums\GeneralStatus::INACTIVE;
                            $ownershipPct = $ownerItem->ownership_percentage ? number_format($ownerItem->ownership_percentage, 0) . '%' : '—';
                        @endphp
                        <tr
                            style="border-bottom: 1px solid #f1f5f9; cursor: pointer; background: transparent;"
                            onmouseover="this.style.background='#f8fafc'"
                            onmouseout="this.style.background='transparent'"
                            onclick="window.location='{{ \App\Filament\Resources\Owners\OwnerResource::getUrl('edit', ['record' => $ownerItem->id]) }}'"
                        >
                            <td style="padding: 0.6rem 0.75rem; font-family: monospace; font-weight: 600; color: #2563eb; white-space: nowrap;">
                                {{ $voucherCode }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; font-weight: 600; color: #1e293b; white-space: nowrap;">
                                {{ $ownerItem->user?->name ?? "مالك #{$ownerItem->id}" }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; font-family: monospace; color: #64748b; white-space: nowrap;">
                                {{ $maskedId }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; font-weight: 600; color: #059669; white-space: nowrap;">
                                {{ $ownershipPct }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; font-family: monospace; color: #475569; white-space: nowrap;">
                                {{ $ownerItem->employment_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; font-family: monospace; color: #475569; white-space: nowrap;">
                                {{ $ownerItem->termination_date?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; font-weight: 600; color: #2563eb; white-space: nowrap;">
                                {{ $duration }}
                            </td>
                            <td style="padding: 0.6rem 0.75rem; text-align: center; white-space: nowrap;">
                                @if($isInactive)
                                    <x-filament::badge color="danger" size="sm">منقطع</x-filament::badge>
                                @else
                                    <x-filament::badge color="success" size="sm">نشط</x-filament::badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                لا يوجد ملاك مسجلين لهذه الشركة حالياً.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
