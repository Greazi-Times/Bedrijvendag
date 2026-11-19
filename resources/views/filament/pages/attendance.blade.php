@php $card = 'rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'; @endphp

<x-filament-panels::page>
    <div class="{{ $card }} grid gap-3 sm:grid-cols-4">
        <label class="text-sm sm:col-span-1">Edition
            <x-filament::input.wrapper class="mt-1"><x-filament::input.select wire:model.live="eventId">
                @foreach ($editions as $edition)<option value="{{ $edition->id }}">{{ $edition->name }}</option>@endforeach
            </x-filament::input.select></x-filament::input.wrapper>
        </label>
        <label class="text-sm sm:col-span-1">Search company or stand
            <x-filament::input.wrapper class="mt-1"><x-filament::input type="search" wire:model.live.debounce.300ms="search" /></x-filament::input.wrapper>
        </label>
        <label class="text-sm">Status
            <x-filament::input.wrapper class="mt-1"><x-filament::input.select wire:model.live="status">
                <option value="all">All</option><option value="present">Present</option><option value="not_checked_in">Not checked in</option>
            </x-filament::input.select></x-filament::input.wrapper>
        </label>
        <label class="text-sm">Sort by
            <x-filament::input.wrapper class="mt-1"><x-filament::input.select wire:model.live="sort">
                <option value="name">Company</option><option value="stand">Stand</option><option value="checked_in">Check-in time</option>
            </x-filament::input.select></x-filament::input.wrapper>
        </label>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['Participating companies', $stats['total']], ['Present', $stats['present']], ['Not checked in', $stats['missing']], ['Attendance', $stats['percentage'].'%']] as [$label, $value])
            <div class="{{ $card }}"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-1 text-3xl font-semibold text-gray-950 dark:text-white">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="grid grid-cols-3 gap-4">
        @foreach ([['Lunch registered', $lunchStats['registered']], ['Lunch checked in', $lunchStats['in']], ['Lunch remaining', $lunchStats['remaining']]] as [$label, $value])
            <div class="{{ $card }}"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-1 text-3xl font-semibold text-gray-950 dark:text-white">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="{{ $card }} overflow-x-auto !p-0">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-gray-500 dark:border-white/10">
                <tr><th class="p-3">Company</th><th class="p-3">Stand</th><th class="p-3">Status</th><th class="p-3">Lunch</th><th class="hidden p-3 sm:table-cell">Checked in</th><th class="p-3 text-right">Action</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr wire:key="company-{{ $row['id'] }}" class="border-b border-gray-100 last:border-0 dark:border-white/5">
                        <td class="p-3 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                        <td class="p-3">{{ implode(', ', $row['stands']) }}</td>
                        <td class="p-3">
                            @if ($row['checked_in_at'])<x-filament::badge color="success">Present</x-filament::badge>@else<x-filament::badge color="gray">Not checked in</x-filament::badge>@endif
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            @if ($row['lunch_registered'])
                                <span class="font-medium">{{ $row['lunch_in'] }} / {{ $row['lunch_registered'] }}</span>
                            @else
                                <span class="text-gray-500">No lunch registration</span>
                                @if ($row['lunch_in'])<span class="block text-xs text-gray-500">{{ $row['lunch_in'] }} recorded</span>@endif
                            @endif
                        </td>
                        <td class="hidden p-3 sm:table-cell">{{ $row['checked_in_at']?->timezone(config('privacy.timezone'))->format('d-m-Y H:i') ?? '—' }}</td>
                        <td class="p-3 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($row['lunch_registered'])
                                    <x-filament::button size="xs" color="info" wire:click="openLunch({{ $row['id'] }})">Lunch check-in</x-filament::button>
                                @endif
                                @if ($row['checked_in_at'])
                                    <x-filament::button size="xs" color="gray" wire:click="showDetails({{ $row['id'] }})">Details</x-filament::button>
                                    @if ($canUpdate)<x-filament::button size="xs" color="danger" wire:click="reverse({{ $row['id'] }})" wire:confirm="Undo this check-in?">Undo</x-filament::button>@endif
                                @elseif ($canUpdate)
                                    <x-filament::button size="xs" wire:click="checkIn({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="checkIn,reverse">Mark as present</x-filament::button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-gray-500">No companies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($detail)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="attendance-title" wire:key="attendance-detail">
            <div class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
                <h2 id="attendance-title" class="text-xl font-semibold text-gray-950 dark:text-white">{{ $justCheckedIn ? 'Company Checked In' : 'Check-in details' }}</h2>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Company</dt><dd class="text-right font-medium">{{ $detail['name'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Status</dt><dd class="text-right">{{ $detail['checked_in_at'] ? 'Present' : 'Not checked in' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Checked in</dt><dd class="text-right">{{ $detail['checked_in_at']?->timezone(config('privacy.timezone'))->format('d-m-Y H:i') ?? '—' }}</dd></div>
                </dl>
                <div class="rounded-lg bg-primary-50 p-4 text-center dark:bg-primary-500/10">
                    <p class="text-sm text-gray-500">{{ count($detail['stands']) > 1 ? 'Stands' : 'Stand' }}</p>
                    <p class="text-5xl font-bold text-primary-600 dark:text-primary-400">{{ implode(', ', $detail['stands']) }}</p>
                </div>
                @if ($dietary !== null)
                    <div class="border-t border-gray-200 pt-3 dark:border-white/10">
                        <h3 class="text-sm font-semibold">Dietary wishes</h3>
                        @if ($dietary)
                            <ul class="mt-1 list-disc pl-5 text-sm">@foreach ($dietary as $line)<li>{{ $line }}</li>@endforeach</ul>
                        @else
                            <p class="mt-1 text-sm text-gray-500">No dietary information provided</p>
                        @endif
                    </div>
                @endif
                <x-filament::button class="w-full" wire:click="closeDetails">Close</x-filament::button>
            </div>
        </div>
    @endif

    @if ($lunch)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="lunch-title" wire:key="lunch-modal">
            <div class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900" x-data="{ max: @js($lunch['lunch_registered']) }">
                <h2 id="lunch-title" class="text-xl font-semibold text-gray-950 dark:text-white">Lunch check-in</h2>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Company</dt><dd class="text-right font-medium">{{ $lunch['name'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ count($lunch['stands']) > 1 ? 'Stands' : 'Stand' }}</dt><dd class="text-right font-medium">{{ implode(', ', $lunch['stands']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Registered participants</dt><dd class="text-right">{{ $lunch['lunch_registered'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Checked in for lunch</dt><dd class="text-right">{{ $lunch['lunch_in'] }}</dd></div>
                </dl>
                @if ($canUpdate)
                    <div class="rounded-lg bg-primary-50 p-4 text-center dark:bg-primary-500/10">
                        <label for="lunch-count" class="text-sm text-gray-500">Participants arrived for lunch (of {{ $lunch['lunch_registered'] }})</label>
                        <div class="mt-2 flex items-center justify-center gap-3">
                            <x-filament::button color="gray" size="lg" x-on:click="$wire.lunchCount = Math.max(0, (parseInt($wire.lunchCount) || 0) - 1)" aria-label="One less">−</x-filament::button>
                            <input id="lunch-count" type="number" min="0" :max="max" inputmode="numeric" wire:model="lunchCount" class="w-24 rounded-lg border-gray-300 text-center text-3xl font-bold dark:bg-white/5 dark:text-white" />
                            <x-filament::button color="gray" size="lg" x-on:click="$wire.lunchCount = Math.min(max, (parseInt($wire.lunchCount) || 0) + 1)" aria-label="One more">+</x-filament::button>
                        </div>
                        @error('lunchCount')<p class="mt-2 text-sm text-danger-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endif
                @if ($lunchDietary !== null)
                    <div class="border-t border-gray-200 pt-3 dark:border-white/10">
                        <h3 class="text-sm font-semibold">Dietary wishes</h3>
                        @if ($lunchDietary)
                            <ul class="mt-1 list-disc pl-5 text-sm">@foreach ($lunchDietary as $line)<li>{{ $line }}</li>@endforeach</ul>
                        @else
                            <p class="mt-1 text-sm text-gray-500">No dietary information provided</p>
                        @endif
                    </div>
                @endif
                <div class="flex gap-2">
                    <x-filament::button class="flex-1" color="gray" wire:click="closeLunch">Close</x-filament::button>
                    @if ($canUpdate)<x-filament::button class="flex-1" wire:click="saveLunch" wire:loading.attr="disabled" wire:target="saveLunch">Save</x-filament::button>@endif
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
