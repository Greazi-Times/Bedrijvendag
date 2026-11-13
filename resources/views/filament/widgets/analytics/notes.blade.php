<x-filament-widgets::widget>
    <x-filament::section heading="Definities en beperkingen" :collapsible="true" :collapsed="true" icon="heroicon-o-information-circle">
        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
            @foreach ($notes as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
