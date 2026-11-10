@php
    $enrollment = $getRecord();
    $revisions = $enrollment->infoSubmissions()->with(['formVersion', 'profileSubmission'])->get();
    $latest = $revisions->first();
@endphp
@if (! $latest)
    <p class="text-sm text-gray-500">The company has not submitted the information form yet.</p>
@else
    <div class="space-y-4 text-sm">
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-gray-500">
            <span>Revision <strong class="text-gray-900 dark:text-white">{{ $latest->revision }}</strong></span>
            <span>First submitted {{ $revisions->last()->submitted_at->format('Y-m-d H:i') }}</span>
            <span>Last changed {{ $latest->submitted_at->format('Y-m-d H:i') }}</span>
            <span>Form version {{ $latest->formVersion->version }}</span>
        </div>

        <div>
            <span class="font-medium">Company profile verification:</span>
            @if ($latest->profile_status === 'confirmed')
                <span class="rounded bg-success-50 px-1.5 text-xs text-success-700">Confirmed correct</span>
            @elseif ($latest->profile_status === 'correction_requested')
                <span class="rounded bg-warning-50 px-1.5 text-xs text-warning-700">Corrections requested</span>
                @if ($latest->profileSubmission)
                    <a class="ml-2 text-primary-600 underline" href="{{ \App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource::getUrl('view', ['record' => $latest->profileSubmission]) }}">Open correction request ({{ $latest->profileSubmission->status }})</a>
                @else
                    <span class="text-gray-500">(no review item: nothing differed from the stored profile)</span>
                @endif
            @else
                <span class="text-gray-500">Not answered</span>
            @endif
        </div>

        <div class="space-y-1">
            @foreach (\App\Support\EnrollmentPresenter::rows($latest->formVersion->schema, $latest->answers ?? [], $latest->files ?? []) as $row)
                @if ($row['type'] === 'section')
                    <div class="pt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $row['label'] }}</div>
                @elseif ($row['type'] === 'entry')
                    <div class="text-xs text-gray-500" style="padding-left: {{ $row['depth'] }}rem">{{ $row['label'] }}</div>
                @else
                    <div class="flex gap-3" style="padding-left: {{ $row['depth'] }}rem">
                        <div class="w-2/5 shrink-0 text-gray-500">{{ $row['label'] }}@if ($row['system'])<span class="block text-xs text-primary-600">{{ $row['system'] }}</span>@endif</div>
                        <div class="min-w-0 flex-1 whitespace-pre-line break-words">
                            {{ $row['value'] }}
                            @if ($row['file'])
                                <a class="ml-2 text-primary-600 underline" href="{{ route('enrollment.info-file', [$latest, $row['file']]) }}">Download</a>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if ($revisions->count() > 1)
            <details class="pt-2">
                <summary class="cursor-pointer text-gray-500">Earlier revisions ({{ $revisions->count() - 1 }})</summary>
                @foreach ($revisions->skip(1) as $old)
                    <div class="mt-3 rounded border border-gray-200 p-3 dark:border-white/10">
                        <div class="mb-1 text-xs text-gray-500">Revision {{ $old->revision }}, {{ $old->submitted_at->format('Y-m-d H:i') }}, form version {{ $old->formVersion->version }}</div>
                        @foreach (\App\Support\EnrollmentPresenter::rows($old->formVersion->schema, $old->answers ?? [], $old->files ?? []) as $row)
                            @if (! in_array($row['type'], ['section', 'entry']))
                                <div class="flex gap-3 text-xs" style="padding-left: {{ $row['depth'] }}rem"><div class="w-2/5 shrink-0 text-gray-500">{{ $row['label'] }}</div><div class="min-w-0 flex-1 whitespace-pre-line break-words">{{ $row['value'] }}</div></div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </details>
        @endif
    </div>
@endif
