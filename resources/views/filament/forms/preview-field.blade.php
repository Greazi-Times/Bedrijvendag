<div>
    <div class="font-medium">{{ $field['label'] ?? '' }}@if (! empty($field['required']))<span class="text-danger-600"> *</span>@endif
        <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $field['type'] }}</span>
        @if (! empty($field['maps_to']))<span class="ml-1 text-xs text-primary-600">system question</span>@endif
    </div>
    @if (($field['type'] ?? '') === 'profile_verification')
        <div class="mt-1 rounded border border-primary-200 bg-primary-50 p-2 text-xs text-primary-800 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-200">
            System component: shows the company's stored profile (name, description, logo, website, educations, sectors) and asks "Are your company details still correct?". “Yes” records the confirmation; “No” opens the profile editor. Changes are sent to the profile review queue and never published directly.
        </div>
    @endif
    @if (! empty($field['description']))<div class="text-gray-500">{{ $field['description'] }}</div>@endif
    @if (! empty($field['options']))<div class="text-gray-500">Options: {{ collect($field['options'])->pluck('label')->implode(', ') }}</div>@endif
    @if (($field['type'] ?? '') === 'rating')<div class="text-gray-500">Rating 1–{{ $field['max'] ?? 5 }}@if (! empty($field['label_low'])) (1 = {{ $field['label_low'] }}@if (! empty($field['label_high'])), {{ $field['max'] ?? 5 }} = {{ $field['label_high'] }}@endif)@endif @if (! empty($field['allow_na']))· “not applicable” offered @endif</div>@endif
    @if (! empty($field['options_source']))<div class="text-gray-500">Options from: {{ $field['options_source'] }}</div>@endif
    @if (! empty($field['visible_if']))<div class="text-xs text-gray-500">Shown when “{{ $labels[$field['visible_if']['field']] ?? 'another question' }}” {{ $field['visible_if']['operator'] }} {{ is_array($field['visible_if']['value'] ?? null) ? implode(',', $field['visible_if']['value']) : ($field['visible_if']['value'] ?? '') }}</div>@endif
    @if (! empty($field['children']))
        <div class="ml-4 mt-2 space-y-2 border-l pl-3 dark:border-white/10">
            @foreach ($field['children'] as $child) @include('filament.forms.preview-field', ['field' => $child, 'labels' => $labels]) @endforeach
        </div>
    @endif
</div>
