<x-filament-widgets::widget>
    <x-filament::section :heading="$mode === 'all' ? 'Alle edities naast elkaar' : 'Geselecteerde editie vergeleken'" :description="$mode === 'all' ? 'Elke editie in een kolom. Niet vastgelegd betekent dat de gegevens niet zijn bewaard of nooit zijn verzameld.' : 'Absoluut verschil en procentuele verandering ten opzichte van de vergelijkingseditie.'">
        @if (count($table['editions']) < ($mode === 'all' ? 1 : 2))
            <p class="text-sm text-gray-500 dark:text-gray-400">Kies een editie en een vergelijkingseditie om te vergelijken.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[32rem] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="px-2 py-2 font-medium">Cijfer</th>
                            @foreach ($table['editions'] as $edition)
                                <th class="whitespace-nowrap px-2 py-2 text-right font-medium">{{ $edition['name'] }}@if ($edition['provisional']) <span class="text-warning-600 dark:text-warning-400">(voorlopig)</span>@endif</th>
                            @endforeach
                            @if ($mode !== 'all')
                                <th class="px-2 py-2 text-right font-medium">Verschil</th>
                                <th class="px-2 py-2 text-right font-medium">%</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table['rows'] as $row)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5" @if ($row['note']) title="{{ $row['note'] }}" @endif>
                                <td class="px-2 py-2 font-medium">{{ $row['label'] }}</td>
                                @foreach ($row['values'] as $i => $value)
                                    <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums {{ $row['missing'][$i] ? 'text-gray-400 italic dark:text-gray-500' : '' }}">{{ $value }}</td>
                                @endforeach
                                @if ($mode !== 'all')
                                    <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums">{{ $row['absolute'] }}</td>
                                    <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums">{{ $row['percent'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
