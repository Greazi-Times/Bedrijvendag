@php
    // Conditions refer to questions by their label, never by the internal identifier.
    $labels = [];
    $collect = function (array $fields) use (&$collect, &$labels): void {
        foreach ($fields as $f) {
            $labels[$f['key'] ?? ''] = $f['label'] ?? 'Untitled question';
            $collect($f['children'] ?? []);
        }
    };
    foreach ($schema['sections'] as $s) { $collect($s['fields'] ?? []); }
@endphp
<div class="space-y-4 text-sm">
    @if ($problems)
        <div class="rounded-lg bg-danger-50 p-3 text-danger-700 dark:bg-danger-950 dark:text-danger-300">
            <strong>Not publishable yet:</strong>
            <ul class="list-disc pl-5">@foreach ($problems as $p)<li>{{ $p }}</li>@endforeach</ul>
        </div>
    @endif
    @foreach ($schema['sections'] as $section)
        <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
            <h3 class="text-base font-semibold">{{ $section['title'] ?? '' }}</h3>
            @if (! empty($section['description']))<p class="text-gray-500">{{ $section['description'] }}</p>@endif
            <div class="mt-3 space-y-3">
                @foreach ($section['fields'] ?? [] as $field)
                    @include('filament.forms.preview-field', ['field' => $field, 'labels' => $labels])
                @endforeach
            </div>
        </div>
    @endforeach
</div>
