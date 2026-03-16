<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end gap-x-3 py-4">
            <x-filament::button
                type="submit"
                form="save"
                color="primary"
            >
                Save Settings
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>