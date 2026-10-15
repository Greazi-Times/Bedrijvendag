{{--
    Review history of one company: every stored submission, newest first.
    Each row is a stored record; "changed" lists the fields that differ from
    the version it replaced, compared on the stored values.
--}}
@php
    use App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource;
    use App\Models\CompanyProfileSubmission;

    $list = $submissions->values();
    $previousVersion = function (int $index) use ($list) {
        $submission = $list[$index];

        return $submission->revision > 1
            ? $list->slice($index + 1)->first(fn ($older) => $older->revision === $submission->revision - 1)
            : null;
    };
@endphp

<div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    <table class="w-full min-w-[40rem] text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                <th scope="col" class="px-4 py-2 text-start">Date</th>
                <th scope="col" class="px-4 py-2 text-start">Event</th>
                <th scope="col" class="px-4 py-2 text-start">Status</th>
                <th scope="col" class="px-4 py-2 text-start"><span class="sr-only">Open</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($list as $index => $submission)
                @php
                    $isCurrent = $submission->is($current);
                    $previous = $previousVersion($index);
                    $changed = $previous ? $submission->changedFieldsComparedTo($previous) : [];
                @endphp
                <tr @class(['bg-primary-50/60 dark:bg-primary-400/5' => $isCurrent])>
                    <td class="whitespace-nowrap px-4 py-3 align-top text-gray-950 dark:text-white">
                        {{ $submission->submitted_at?->format('M j, Y H:i') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 align-top">
                        <p class="font-medium text-gray-950 dark:text-white">
                            {{ $submission->revision > 1 ? "Company updated its pending changes (update {$submission->revision})" : 'Company submitted changes' }}
                        </p>
                        @if ($previous)
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">
                                {{ $changed ? 'Changed: '.implode(', ', $changed) : 'No field changes compared to the previous update' }}
                            </p>
                        @endif
                        @if ($submission->contact_name || $submission->contact_email)
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">By {{ collect([$submission->contact_name, $submission->contact_email])->filter()->implode(' · ') }}</p>
                        @endif
                        @if ($submission->reviewed_at)
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">
                                {{ CompanyProfileSubmission::STATUS_LABELS[$submission->status] ?? ucfirst($submission->status) }}
                                {{ $submission->reviewer ? 'by '.$submission->reviewer->name : '' }}
                                on {{ $submission->reviewed_at->format('M j, Y H:i') }}
                            </p>
                        @endif
                    </td>
                    <td class="px-4 py-3 align-top">
                        <x-filament::badge :color="CompanyProfileSubmission::STATUS_COLORS[$submission->status] ?? 'gray'" class="w-fit">
                            {{ CompanyProfileSubmission::STATUS_LABELS[$submission->status] ?? ucfirst($submission->status) }}
                        </x-filament::badge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-end align-top">
                        @if ($isCurrent)
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Viewing</span>
                        @else
                            <x-filament::link :href="CompanyProfileSubmissionResource::getUrl('view', ['record' => $submission])" size="sm">
                                {{ $submission->isPending() ? 'Review' : 'Open' }}
                            </x-filament::link>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
