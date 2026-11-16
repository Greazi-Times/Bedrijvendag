@props(['title' => null, 'description' => null])
<section {{ $attributes->class('dash-panel') }}>
    @if ($title)
        <header>
            <h3>{{ $title }}</h3>
            @if ($description)<p>{{ $description }}</p>@endif
        </header>
    @endif
    <div>{{ $slot }}</div>
</section>
