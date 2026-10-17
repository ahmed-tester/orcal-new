<x-filament-panels::page>
    <form wire:submit.prevent="saveSettings" class="space-y-6">
        {{ $this->form }}

        <div style="margin-top: 20px; display: flex; align-items: center; justify-content: flex-start; gap: 12px;">
            <x-filament::actions 
                :actions="$this->getFormActions()"
            />
        </div>
    </form>
</x-filament-panels::page>
