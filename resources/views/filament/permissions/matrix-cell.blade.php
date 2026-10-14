@if ($mode === 'edit')
    <input
        type="checkbox"
        value="{{ $name }}"
        x-model="state"
        class="fi-checkbox-input"
        aria-label="{{ $text }}"
        @disabled(! $held->contains($name))
        @if (! $held->contains($name)) title="You don't hold this permission yourself" @endif
    />
@elseif (isset($granted[$name]))
    <span
        class="inline-flex text-success-600 dark:text-success-400"
        x-tooltip="{ content: @js('Allowed via '.implode(', ', $granted[$name])), theme: $store.theme }"
    >
        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::CheckCircle" class="size-5" />
        <span class="sr-only">{{ $text }}: allowed via {{ implode(', ', $granted[$name]) }}</span>
    </span>
@else
    <span class="inline-flex text-gray-300 dark:text-gray-600">
        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::MinusCircle" class="size-5" />
        <span class="sr-only">{{ $text }}: denied</span>
    </span>
@endif
