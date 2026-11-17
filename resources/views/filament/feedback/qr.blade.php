{{-- The PNG is made from the very same SVG in the browser, so it looks exactly like the preview and needs no server extension. --}}
<div
    class="space-y-4 text-center text-sm"
    x-data="{
        svg: @js($svg),
        name: @js("feedback-{$survey->audience}-{$survey->event_id}"),
        busy: false,
        async png(pixels = 1200) {
            this.busy = true
            try {
                const image = new Image()
                image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(this.svg)
                await image.decode()
                const canvas = document.createElement('canvas')
                canvas.width = canvas.height = pixels
                const context = canvas.getContext('2d')
                context.fillStyle = '#fff'
                context.fillRect(0, 0, pixels, pixels)
                context.drawImage(image, 0, 0, pixels, pixels)
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'))
                const link = document.createElement('a')
                link.href = URL.createObjectURL(blob)
                link.download = this.name + '.png'
                link.click()
                setTimeout(() => URL.revokeObjectURL(link.href), 1000)
            } finally {
                this.busy = false
            }
        },
    }"
>
    <div class="mx-auto w-64 rounded-xl bg-white p-3 ring-1 ring-gray-200 [&>svg]:h-auto [&>svg]:w-full">{!! $svg !!}</div>
    <p class="break-all text-gray-500">{{ $survey->url() }}</p>
    <div class="flex justify-center gap-4">
        <button type="button" x-on:click="png()" x-bind:disabled="busy" class="fi-link text-primary-600 underline">Download PNG</button>
        <a href="{{ route('feedback.qr', ['survey' => $survey, 'format' => 'svg']) }}" class="fi-link text-primary-600 underline">Download SVG</a>
    </div>
    @if ($survey->state() !== 'open')
        <p class="text-warning-600">This form is currently not open. The code stays valid: it works again as soon as the form is open.</p>
    @endif
</div>
