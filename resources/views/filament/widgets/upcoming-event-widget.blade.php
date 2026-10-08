<x-filament-widgets::widget>
    <x-filament::section>
        @if ($event)
            <div class="flex items-center justify-between gap-6">
                <div class="min-w-0">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Next event in</div>
                    <div class="mt-1 text-3xl font-semibold leading-tight">
                        {{ $countdown }}
                    </div>

                    <div class="mt-4 space-y-1">
                        <div class="truncate text-base font-medium">
                            {{ $event->name ?? 'Event' }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($event->date)->format('d-m-Y') }}
                        </div>
                    </div>
                </div>

                <div class="shrink-0 rounded-lg border border-gray-200 px-4 py-3 text-center dark:border-white/10">
                    <div class="text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400">
                        {{ \Illuminate\Support\Carbon::parse($event->date)->locale(app()->getLocale())->isoFormat('MMM') }}
                    </div>
                    <div class="text-3xl font-semibold tabular-nums leading-none">
                        {{ \Illuminate\Support\Carbon::parse($event->date)->format('j') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($event->date)->locale(app()->getLocale())->isoFormat('dddd') }}
                    </div>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between gap-6">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Next event</div>
                    <div class="mt-1 text-xl font-semibold">When is the next event?</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Even the calendar doesn't know.</div>
                </div>

            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
