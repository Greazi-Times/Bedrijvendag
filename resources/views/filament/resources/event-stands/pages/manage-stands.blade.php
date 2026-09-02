<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @php
            $markerStand = $this->getMarkerEditorStand();
            $mapUrl = $this->getMarkerEditorMapUrl();
            $markers = $this->getMarkerEditorMarkers();
            $standCode = $markerStand
                ? ($markerStand->type === 'partner'
                    ? 'P'.preg_replace('/^P/i', '', (string) $markerStand->stand_number)
                    : (string) $markerStand->stand_number)
                : null;
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

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
