<x-filament-widgets::widget>
    <x-filament::section heading="Retentiecohorten" description="Bedrijven gegroepeerd op hun eerste editie en hoeveel er daarna weer meededen. Lege cellen zijn nog niet bekend (editie bestaat niet of is voorlopig), geen nul.">
        @if ($cohorts === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">Geen gegevens beschikbaar.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[32rem] border-separate border-spacing-1 text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                            <th class="px-2 py-1 font-medium">Eerste editie</th>
                            <th class="px-2 py-1 text-right font-medium">Start</th>
                            @for ($d = 1; $d < $width; $d++)
                                <th class="px-2 py-1 text-center font-medium">{{ $d === 1 ? 'Volgende editie' : $d.' edities later' }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cohorts as $cohort)
                            <tr>
                                <td class="whitespace-nowrap px-2 py-1 font-medium">{{ $cohort['name'] }}</td>
                                <td class="px-2 py-1 text-right tabular-nums">{{ $cohort['size'] }}</td>
                                @for ($d = 1; $d < $width; $d++)
                                    @php
                                        $value = $cohort['cells'][$d] ?? null;
                                        $pct = $value !== null && $cohort['size'] > 0 ? round($value / $cohort['size'] * 100) : null;
                                    @endphp
                                    <td class="rounded px-2 py-1 text-center tabular-nums"
                                        @if ($pct !== null) style="background: rgba(255, 102, 0, {{ max(0.06, $pct / 100 * 0.8) }})" title="{{ $value }} van {{ $cohort['size'] }} bedrijven" @endif>
                                        @if ($value === null)
                                            <span class="text-gray-400 dark:text-gray-500">–</span>
                                        @else
                                            <span class="font-medium">{{ $value }}</span>
                                            <span class="ml-1 text-xs opacity-80">{{ $pct === null ? '' : $pct.'%' }}</span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
