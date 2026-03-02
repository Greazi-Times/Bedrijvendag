<x-filament-panels::page>
    <div>
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        <div style="height: 24px;"></div>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
