<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @if ($this->pointEditorMode)
            <div class="fixed inset-0 z-[999] flex items-center justify-center p-4">
                <button
                    type="button"
                    class="absolute inset-0 bg-gray-950/50"
                    wire:click="closePointEditor"
                    aria-label="Close map location editor"
                ></button>

                <form
                    wire:submit.prevent="savePointEditor"
                    class="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/20"
                >
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-white/20">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $this->pointEditorMode === 'create' ? 'Add map location' : 'Edit map location' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-200">
                                This location belongs to the selected event map.
                            </p>
                        </div>

                        <x-filament::button color="gray" type="button" wire:click="closePointEditor">
                            Close
                        </x-filament::button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto p-5">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">Type</span>
                            <select
                                wire:model="pointData.type"
                                class="block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/20 dark:bg-white/10 dark:text-white"
                            >
                                @foreach ($this->pointTypeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('pointData.type')
                                <span class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <div class="flex shrink-0 justify-end gap-3 border-t border-gray-200 px-5 py-4 dark:border-white/20">
                        <x-filament::button color="gray" type="button" wire:click="closePointEditor">
                            Cancel
                        </x-filament::button>

                        <x-filament::button type="submit">
                            Save location
                        </x-filament::button>
                    </div>
                </form>
            </div>
        @endif

        @php
            $markerStand = $this->getMarkerEditorStand();
            $markerPoint = $this->getMarkerEditorPoint();
            $mapUrl = $this->getMarkerEditorMapUrl();
            $markers = $this->getMarkerEditorMarkers();
            $standCode = $markerStand
                ? ($markerStand->type === 'partner'
                    ? 'P'.preg_replace('/^P/i', '', (string) $markerStand->stand_number)
                    : (string) $markerStand->stand_number)
                : null;
            $pointMarkers = $this->getPointMarkerEditorMarkers();
            $pointCode = $markerPoint
                ? match ($markerPoint->type) {
                    'bar' => 'B',
                    'info' => 'i',
                    'lunch' => 'L',
                    'entrance' => 'E',
                    default => strtoupper(substr($markerPoint->label, 0, 1)),
                }
                : null;
            $pointLabel = $markerPoint ? ($this->pointTypeOptions()[$markerPoint->type] ?? $markerPoint->label) : null;
        @endphp

        @if ($markerStand)
            <div class="fixed inset-0 z-[999] flex items-center justify-center p-4">
                <button
                    type="button"
                    class="absolute inset-0 bg-gray-950/50"
                    wire:click="closeMarkerEditor"
                    aria-label="Close marker editor"
                ></button>

                <div class="relative z-10 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex shrink-0 flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                                Set marker for stand {{ $standCode }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                Click the shared map to save this stand's position. Existing markers are shown for reference.
                            </p>
                        </div>

                        <x-filament::button color="gray" wire:click="closeMarkerEditor">
                            Close
                        </x-filament::button>
                    </div>

                    <div
                        x-data="{
                            lwId: @js($this->getId()),
                            standId: @js($markerStand->id),
                            standType: @js($markerStand->type),
                            standCode: @js($standCode),
                            markers: @js($markers),
                            saved: false,
                            x: @js(is_numeric($markerStand->x_percent) ? (float) $markerStand->x_percent : null),
                            y: @js(is_numeric($markerStand->y_percent) ? (float) $markerStand->y_percent : null),
                            click(event) {
                                const rect = this.$refs.mapLayer.getBoundingClientRect();
                                const x = ((event.clientX - rect.left) / rect.width) * 100;
                                const y = ((event.clientY - rect.top) / rect.height) * 100;
                                this.x = Math.max(0, Math.min(100, Math.round(x * 100) / 100));
                                this.y = Math.max(0, Math.min(100, Math.round(y * 100) / 100));

                                const current = this.markers.find((marker) => marker.id === this.standId);
                                if (current) {
                                    current.x = this.x;
                                    current.y = this.y;
                                    current.current = true;
                                } else {
                                    this.markers.push({
                                        id: this.standId,
                                        code: this.standCode,
                                        type: this.standType,
                                        x: this.x,
                                        y: this.y,
                                        current: true,
                                    });
                                }

                                this.saved = true;
                                if (window.Livewire && window.Livewire.find) {
                                    const livewire = window.Livewire.find(this.lwId);
                                    if (livewire) {
                                        livewire.call('setMarkerForStand', this.standId, this.x, this.y);
                                    }
                                }
                            },
                        }"
                        class="min-h-0 flex-1 space-y-4 overflow-auto p-5"
                    >
                        <div class="flex justify-end">
                            <div
                                x-show="saved || (x !== null && y !== null)"
                                x-cloak
                                class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-100"
                            >
                                X: <span x-text="x"></span>%, Y: <span x-text="y"></span>%
                            </div>
                        </div>

                        @if ($mapUrl)
                            <div
                                x-ref="mapLayer"
                                class="relative mx-auto inline-block overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                @click.stop.prevent="click($event)"
                            >
                                <img
                                    src="{{ $mapUrl }}"
                                    alt="Event map"
                                    class="block h-auto w-auto max-h-[72vh] max-w-full cursor-crosshair select-none"
                                    draggable="false"
                                />

                                <template x-for="marker in markers" :key="marker.id">
                                    <div
                                        class="pointer-events-none absolute flex h-6 min-w-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full px-1.5 text-[11px] font-bold text-white shadow-md ring-2 ring-white/90"
                                        :class="marker.current ? 'bg-blue-600 ring-blue-200' : (marker.type === 'partner' ? 'bg-amber-500' : 'bg-primary-600')"
                                        :style="`left: ${marker.x}%; top: ${marker.y}%`"
                                    >
                                        <span x-text="marker.code"></span>
                                    </div>
                                </template>
                            </div>
                        @else
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                No shared event map has been uploaded yet. Add one in Settings > Page media.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($markerPoint)
            <div class="fixed inset-0 z-[999] flex items-center justify-center p-4">
                <button
                    type="button"
                    class="absolute inset-0 bg-gray-950/50"
                    wire:click="closePointMarkerEditor"
                    aria-label="Close marker editor"
                ></button>

                <div class="relative z-10 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/20">
                    <div class="flex shrink-0 flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/20 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                                Set marker for {{ $pointLabel }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-200">
                                Click the shared map to save this location's position.
                            </p>
                        </div>

                        <x-filament::button color="gray" wire:click="closePointMarkerEditor">
                            Close
                        </x-filament::button>
                    </div>

                    <div
                        x-data="{
                            lwId: @js($this->getId()),
                            pointId: @js($markerPoint->id),
                            pointType: @js($markerPoint->type),
                            pointLabel: @js($pointLabel),
                            pointCode: @js($pointCode),
                            markers: @js($pointMarkers),
                            saved: false,
                            x: @js(is_numeric($markerPoint->x_percent) ? (float) $markerPoint->x_percent : null),
                            y: @js(is_numeric($markerPoint->y_percent) ? (float) $markerPoint->y_percent : null),
                            click(event) {
                                const rect = this.$refs.mapLayer.getBoundingClientRect();
                                const x = ((event.clientX - rect.left) / rect.width) * 100;
                                const y = ((event.clientY - rect.top) / rect.height) * 100;
                                this.x = Math.max(0, Math.min(100, Math.round(x * 100) / 100));
                                this.y = Math.max(0, Math.min(100, Math.round(y * 100) / 100));

                                const current = this.markers.find((marker) => marker.id === this.pointId);
                                if (current) {
                                    current.x = this.x;
                                    current.y = this.y;
                                    current.current = true;
                                } else {
                                    this.markers.push({
                                        id: this.pointId,
                                        key: `point-${this.pointId}`,
                                        label: this.pointLabel,
                                        code: this.pointCode,
                                        type: this.pointType,
                                        x: this.x,
                                        y: this.y,
                                        current: true,
                                    });
                                }

                                this.saved = true;
                                if (window.Livewire && window.Livewire.find) {
                                    const livewire = window.Livewire.find(this.lwId);
                                    if (livewire) {
                                        livewire.call('setMarkerForPoint', this.pointId, this.x, this.y);
                                    }
                                }
                            },
                        }"
                        class="min-h-0 flex-1 space-y-4 overflow-auto p-5"
                    >
                        <div class="flex justify-end">
                            <div
                                x-show="saved || (x !== null && y !== null)"
                                x-cloak
                                class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-100"
                            >
                                X: <span x-text="x"></span>%, Y: <span x-text="y"></span>%
                            </div>
                        </div>

                        @if ($mapUrl)
                            <div
                                x-ref="mapLayer"
                                class="relative mx-auto inline-block overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                @click.stop.prevent="click($event)"
                            >
                                <img
                                    src="{{ $mapUrl }}"
                                    alt="Event map"
                                    class="block h-auto w-auto max-h-[72vh] max-w-full cursor-crosshair select-none"
                                    draggable="false"
                                />

                                <template x-for="marker in markers" :key="marker.key">
                                    <div
                                        class="pointer-events-none absolute flex h-9 min-w-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full px-2 text-xs font-bold shadow-lg ring-2"
                                        :class="
                                            marker.current
                                                ? 'bg-blue-600 text-white ring-blue-200'
                                                : marker.type === 'bar'
                                                  ? 'bg-amber-500 text-amber-950 ring-white/95'
                                                  : marker.type === 'info'
                                                    ? '!h-7 !w-7 !min-w-7 !px-0 bg-sky-500 text-white ring-white/95 [&_svg]:h-4 [&_svg]:w-4'
                                                    : marker.type === 'lunch'
                                                      ? 'bg-emerald-500 text-white ring-white/95'
                                                      : marker.type === 'entrance'
                                                        ? 'bg-violet-500 text-white ring-white/95'
                                                        : 'bg-slate-600 text-white ring-white/95'
                                        "
                                        :style="`left: ${marker.x}%; top: ${marker.y}%`"
                                    >
                                        <svg x-show="marker.type === 'bar'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 11h1a3 3 0 0 1 0 6h-1" />
                                            <path d="M9 12v6" />
                                            <path d="M13 12v6" />
                                            <path d="M14 7.5c0 1.4-1.2 2.5-2.7 2.5H8.5C7 10 6 8.9 6 7.5S7.2 5 8.7 5h2.8C13 5 14 6.1 14 7.5Z" />
                                            <path d="M6 10h11v8a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2Z" />
                                        </svg>
                                        <svg x-show="marker.type === 'info'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 10v6" />
                                            <path d="M12 7h.01" />
                                        </svg>
                                        <svg x-show="marker.type === 'lunch'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 3v8" />
                                            <path d="M8 3v8" />
                                            <path d="M4 7h4" />
                                            <path d="M6 11v10" />
                                            <path d="M18 3v18" />
                                            <path d="M14 7a4 4 0 0 1 4-4" />
                                        </svg>
                                        <svg x-show="marker.type === 'entrance'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13 4h5v16h-5" />
                                            <path d="M6 12h10" />
                                            <path d="m13 9 3 3-3 3" />
                                        </svg>
                                        <span x-show="!['bar', 'info', 'lunch', 'entrance'].includes(marker.type)" x-text="marker.code"></span>
                                    </div>
                                </template>
                            </div>
                        @else
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                No shared event map has been uploaded yet. Add one in Settings > Page media.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <x-filament::section heading="Stands">
            {{ $this->table }}
        </x-filament::section>

        <x-filament::section heading="Map Locations">
            @php
                $mapLocations = $this->getMapLocations();
            @endphp

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">Marker</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                        @forelse ($mapLocations as $point)
                            <tr wire:key="map-location-{{ $point->id }}">
                                <td class="px-4 py-3 text-gray-950 dark:text-white">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                        {{ $this->pointTypeOptions()[$point->type] ?? $point->label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ is_numeric($point->x_percent) && is_numeric($point->y_percent) ? 'Set' : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-filament::button size="sm" color="gray" icon="heroicon-o-map-pin" wire:click="openMarkerEditorForPoint({{ $point->id }})">
                                            Set marker
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="openEditPointEditor({{ $point->id }})">
                                            Edit
                                        </x-filament::button>
                                        @if (is_numeric($point->x_percent) && is_numeric($point->y_percent))
                                            <x-filament::button size="sm" color="danger" wire:click="clearMarkerForPoint({{ $point->id }})" wire:confirm="Clear this marker?">
                                                Clear marker
                                            </x-filament::button>
                                        @endif
                                        <x-filament::button size="sm" color="danger" wire:click="deletePoint({{ $point->id }})" wire:confirm="Delete this map location?">
                                            Delete
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No map locations have been added for this event yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
