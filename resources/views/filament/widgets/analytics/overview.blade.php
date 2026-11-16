<x-filament-widgets::widget>
    <div class="dash-stack">
        <div>
            <h2 class="dash-heading" style="font-size: 1.25rem">{{ $event?->name ?? 'Alle edities' }}</h2>
            <p class="dash-desc">
                @if ($event)
                    {{ $event->date->translatedFormat('j F Y') }} · {{ $event->date->isFuture() ? 'Aankomend' : ($event->date->isToday() ? 'Vandaag' : 'Afgelopen') }}
                    @if ($compare) · Vergeleken met {{ $compare->name }} @endif
                @else
                    Totalen over alle edities; vergelijkingen en standcijfers vragen om een gekozen editie.
                @endif
            </p>
        </div>

        @if ($primary !== [])
            <div class="dash-grid" role="list" aria-label="Belangrijkste cijfers">
                @foreach ($primary as $card)
                    <x-dashboard.card :label="$card['label']" :value="$card['value']" :sub="$card['sub'] ?? null" :tone="$card['tone']" :url="$card['url'] ?? null" :hint="$card['hint'] ?? null" :progress="$card['progress'] ?? null" />
                @endforeach
            </div>
        @endif

        <div class="dash-two">
            @if ($secondary !== [])
                <x-dashboard.panel title="Verder in de editie" description="Klik op een regel voor de bijbehorende lijst.">
                    <ul class="dash-list">
                        @foreach ($secondary as $row)
                            <x-dashboard.row :label="$row['label']" :count="$row['value']" :url="$row['url'] ?? null" />
                        @endforeach
                    </ul>
                </x-dashboard.panel>
            @endif

            <x-dashboard.panel title="Aandacht nodig" description="Openstaand werk waartoe u toegang heeft.">
                @php($open = collect($attention)->where('count', '>', 0))
                @if ($open->isEmpty())
                    <x-dashboard.empty text="Niets vraagt op dit moment aandacht." />
                @else
                    <ul class="dash-list">
                        @foreach ($open as $item)
                            <x-dashboard.row :label="$item['label']" :count="$item['count']" :tone="$item['color']" :url="$item['url']" :hint="$item['tooltip']" />
                        @endforeach
                    </ul>
                @endif
            </x-dashboard.panel>
        </div>

        @if ($insights !== [])
            <x-dashboard.panel title="Inzichten" description="Afgeleid uit de cijfers hierboven; geen verwachtingen of oorzaken.">
                <ul class="dash-list">
                    @foreach ($insights as $insight)
                        <x-dashboard.row :label="$insight['text']" :url="$insight['url']" />
                    @endforeach
                </ul>
            </x-dashboard.panel>
        @endif
    </div>
</x-filament-widgets::widget>
