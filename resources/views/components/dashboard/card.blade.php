@props(['label', 'value', 'sub' => null, 'tone' => 'gray', 'url' => null, 'hint' => null, 'small' => false, 'progress' => null])
@php
    // Colour tells status only: the dot marks the card, the comparison line takes the tone of the change.
    $tag = $url ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($url) href="{{ $url }}" @endif @if ($hint) title="{{ $hint }}" @endif class="dash-card">
    <span class="dash-label"><span class="dash-dot" data-tone="{{ $tone }}" aria-hidden="true"></span>{{ $label }}</span>
    <span class="dash-value {{ $small ? 'dash-value-sm' : '' }}">{{ $value }}</span>
    @if ($sub)
        {{-- Only a comparison ("t.o.v.") takes a status colour; a share or a count stays neutral. --}}
        <span class="dash-sub" data-tone="{{ str_contains($sub, 't.o.v.') && in_array($tone, ['success', 'danger'], true) ? $tone : 'gray' }}">{{ $sub }}</span>
    @endif
    @if ($progress !== null)
        <span class="dash-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($progress) }}"><span style="width: {{ min(100, max(0, $progress)) }}%"></span></span>
    @endif
</{{ $tag }}>
