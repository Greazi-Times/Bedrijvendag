<x-filament-widgets::widget>
    <div class="dash-stack">
        @if ($title)
            <div>
                <h2 class="dash-heading">{{ $title }}</h2>
                @if ($subtitle)<p class="dash-desc">{{ $subtitle }}</p>@endif
            </div>
        @endif
        <div class="dash-grid dash-grid-4">
            @forelse ($cards as $card)
                <x-dashboard.card :label="$card['label']" :value="$card['value']" :sub="$card['description']" :tone="$card['color']" :url="$card['url']" :hint="$card['tooltip']" small />
            @empty
                <x-dashboard.empty />
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
