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
@if ($sections !== [])
    <div class="overflow-x-auto">
        <x-filament::tabs label="Onderdelen">
            @foreach ($sections as $section)
                <x-filament::tabs.item
                    :active="$section['slug'] === $activeSection"
                    wire:key="dashboard-section-{{ $section['slug'] }}"
                    wire:click="$set('activeSection', '{{ $section['slug'] }}')"
                >
                    {{ $section['label'] }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
    </div>
@endif
@if ($chips !== [])
    <div class="flex flex-wrap items-center gap-2" aria-label="Toegepaste filters">
        @foreach ($chips as $chip)
            <span class="dash-chip">{{ $chip }}</span>
        @endforeach
        <button type="button" wire:click="resetFilters" class="dash-chip" style="cursor: pointer">Filters herstellen</button>
    </div>
@endif
@if (! empty($notice))
    <p class="text-sm" style="color: var(--admin-muted)" role="status">{{ $notice }}</p>
@endif
