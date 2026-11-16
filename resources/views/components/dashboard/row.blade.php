@props(['label', 'count' => null, 'tone' => 'gray', 'url' => null, 'hint' => null])
@php($tag = $url ? 'a' : 'div')
<li>
    <{{ $tag }} @if ($url) href="{{ $url }}" @endif @if ($hint) title="{{ $hint }}" @endif class="dash-row">
        <span class="dash-dot" data-tone="{{ $tone }}" aria-hidden="true"></span>
        <span class="dash-row-label">{{ $label }}</span>
        @if ($count !== null)<span class="dash-row-count">{{ $count }}</span>@endif
    </{{ $tag }}>
</li>
