<div class="overflow-x-auto">
    <x-filament::tabs label="Dashboardonderdelen">
        @foreach ($tabs as $tab)
            <x-filament::tabs.item
                :active="$tab['slug'] === $active"
                :icon="$tab['icon']"
                wire:key="dashboard-tab-{{ $tab['slug'] }}"
                wire:click="$set('activeTab', '{{ $tab['slug'] }}')"
            >
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</div>
