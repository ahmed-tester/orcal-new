<x-filament-panels::page>
    <form wire:submit.prevent="savePolicy" class="space-y-6">
        {{ $this->form }}

        <div style="margin-top: 17px;">
            <x-filament::actions 
                :actions="$this->getFormActions()"
            />
        </div>
    </form>
</x-filament-panels::page>
