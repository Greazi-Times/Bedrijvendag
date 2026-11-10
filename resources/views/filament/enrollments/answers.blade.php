@php($enrollment = $getRecord())
<div class="space-y-1 text-sm">
    @foreach (\App\Support\EnrollmentPresenter::answers($enrollment) as $row)
        @if ($row['type'] === 'section')
            <div class="pt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $row['label'] }}</div>
        @elseif ($row['type'] === 'entry')
            <div class="text-xs text-gray-500" style="padding-left: {{ $row['depth'] * 1 }}rem">{{ $row['label'] }}</div>
        @else
            <div class="flex gap-3" style="padding-left: {{ $row['depth'] * 1 }}rem">
                <div class="w-2/5 shrink-0 text-gray-500">{{ $row['label'] }}@if ($row['system'])<span class="block text-xs text-primary-600">{{ $row['system'] }}</span>@endif</div>
                <div class="min-w-0 flex-1 whitespace-pre-line break-words">
                    {{ $row['value'] }}
                    @if ($row['file'])
                        <a class="ml-2 text-primary-600 underline" href="{{ route('enrollment.file', [$enrollment, $row['file']]) }}">Download</a>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
