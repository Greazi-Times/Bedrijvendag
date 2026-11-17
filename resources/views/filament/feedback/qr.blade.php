<div class="space-y-4 text-center text-sm">
    <div class="mx-auto w-64 rounded-xl bg-white p-3 ring-1 ring-gray-200 [&>svg]:h-auto [&>svg]:w-full">{!! $svg !!}</div>
    <p class="break-all text-gray-500">{{ $survey->url() }}</p>
    <div class="flex justify-center gap-3">
        <a href="{{ route('feedback.qr', ['survey' => $survey, 'format' => 'svg']) }}" class="fi-link text-primary-600 underline">Download SVG</a>
        @if ($png)
            <a href="{{ route('feedback.qr', ['survey' => $survey, 'format' => 'png']) }}" class="fi-link text-primary-600 underline">Download PNG</a>
        @endif
    </div>
    @if ($survey->state() !== 'open')
        <p class="text-warning-600">This form is currently not open. The code stays valid: it works again as soon as the form is open.</p>
    @endif
</div>
