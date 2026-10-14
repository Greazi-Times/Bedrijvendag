{{--
    Before/after view of an audit entry. Model changes are stored as
    properties.old / properties.attributes; explicit events may store old/new
    lists and added/removed. Everything is printed with {{ }}, so logged
    values are always escaped text.
--}}
@php
    $properties = collect($getRecord()->properties ?? []);
    $old = $properties->get('old');
    $new = $properties->get('attributes') ?? $properties->get('new');
    $isList = fn ($value) => is_array($value) && array_is_list($value);
    $fields = is_array($old) && ! $isList($old) || is_array($new) && ! $isList($new)
        ? collect(array_keys(array_merge((array) $old, (array) $new)))
        : collect();
    $format = fn ($value) => match (true) {
        $value === null => '—',
        is_bool($value) => $value ? 'Yes' : 'No',
        is_array($value) => $isList($value) && collect($value)->every(fn ($item) => is_scalar($item))
            ? (implode(', ', $value) ?: '—')
            : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        default => (string) $value,
    };
    $label = fn (string $key) => str($key)->replace(['_', '-', '.'], ' ')->ucfirst();
    $details = $properties->except($fields->isNotEmpty() ? ['old', 'attributes', 'new'] : ['attributes']);
@endphp

<div class="flex flex-col gap-4">
    @if ($fields->isNotEmpty())
        <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <table class="w-full min-w-[36rem] table-fixed text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                        <th scope="col" class="w-48 px-4 py-2 text-start">Field</th>
                        <th scope="col" class="px-4 py-2 text-start">Before</th>
                        <th scope="col" class="px-4 py-2 text-start">After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($fields as $field)
                        <tr>
                            <th scope="row" class="px-4 py-2 text-start align-top font-medium text-gray-950 dark:text-white">{{ $label($field) }}</th>
                            <td class="px-4 py-2 align-top">
                                @if (is_array($old) && array_key_exists($field, $old))
                                    <span class="whitespace-pre-wrap break-words rounded bg-danger-50 px-1 text-danger-700 line-through decoration-danger-400/60 dark:bg-danger-400/10 dark:text-danger-400">{{ $format($old[$field]) }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-top">
                                @if (is_array($new) && array_key_exists($field, $new))
                                    <span class="whitespace-pre-wrap break-words rounded bg-success-50 px-1 text-success-700 dark:bg-success-400/10 dark:text-success-400">{{ $format($new[$field]) }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($details->isNotEmpty())
        <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
            @foreach ($details as $key => $value)
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label((string) $key) }}</dt>
                    <dd class="mt-0.5 whitespace-pre-wrap break-words font-mono text-gray-950 dark:text-white">{{ $format($value) }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($fields->isEmpty() && $details->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No changed values were recorded for this event.</p>
    @endif
</div>
