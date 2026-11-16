@php
    use App\Services\Analytics\FeedbackStats;

    $report = $this->getReport();
    $surveys = $report['surveys'];
    $headline = $report['headline'];
    $canModerate = auth()->user()?->can('feedback.delete');
    $bar = fn (float $percent): string => 'width: '.max(0, min(100, $percent)).'%';
    $score = fn (array $g): string => number_format(FeedbackStats::rating($g)['average'], 2).' / '.$g['max'];
@endphp

<x-filament-panels::page>
    {{-- Filters --}}
    <div class="flex flex-col gap-4 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 sm:flex-row sm:items-start sm:justify-between">
        <fieldset>
            <legend class="mb-2 text-sm font-medium text-gray-950 dark:text-white">Editions <span class="font-normal text-gray-500">(select several to compare)</span></legend>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach ($report['editions'] as $edition)
                    <label class="flex items-center gap-2 text-sm">
                        <x-filament::input.checkbox wire:model.live="events" :value="$edition->id" />
                        {{ $edition->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="flex gap-2" role="group" aria-label="Audience">
            <x-filament::button wire:click="$set('audience', 'students')" :color="$audience === 'students' ? 'primary' : 'gray'" size="sm">Students ({{ $report['students'] }})</x-filament::button>
            <x-filament::button wire:click="$set('audience', 'companies')" :color="$audience === 'companies' ? 'primary' : 'gray'" size="sm">Companies ({{ $report['companies'] }})</x-filament::button>
        </div>
    </div>

    @if ($surveys->isEmpty())
        <p class="text-sm text-gray-500">Select at least one edition.</p>
    @else
        {{-- Overview --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Total responses', $report['total'], 'All audiences, selected editions'], ['Student responses', $report['students'], null], ['Company responses', $report['companies'], null]] as [$label, $value, $hint])
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-950 dark:text-white">{{ $value }}</p>
                    @if ($hint)<p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>@endif
                </div>
            @endforeach
            <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <p class="text-sm text-gray-500">{{ $headline['label'] ?? 'Overall rating' }}</p>
                @if ($headline && FeedbackStats::rating($headline)['n'])
                    <p class="mt-1 text-3xl font-semibold text-gray-950 dark:text-white">{{ $score($headline) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ FeedbackStats::rating($headline)['n'] }} ratings, {{ count($headline['per_survey']) }} {{ str('edition')->plural(count($headline['per_survey'])) }} with this exact scale</p>
                @else
                    <p class="mt-1 text-sm text-gray-500">No ratings yet</p>
                @endif
            </div>
        </div>

        {{-- Trend and best/worst --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 lg:col-span-1">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Responses per day</h3>
                @if ($report['trend']->isEmpty())
                    <p class="mt-3 text-sm text-gray-500">No responses yet.</p>
                @else
                    @php $peak = max(1, $report['trend']->max()); @endphp
                    <div class="mt-4 flex h-28 items-end gap-1" role="img" aria-label="Responses per day">
                        @foreach ($report['trend'] as $day => $count)
                            <div class="flex-1 rounded-t bg-primary-500" style="height: {{ max(4, $count / $peak * 100) }}%" title="{{ $day }}: {{ $count }}"></div>
                        @endforeach
                    </div>
                    <p class="mt-2 flex justify-between text-xs text-gray-500"><span>{{ $report['trend']->keys()->first() }}</span><span>{{ $report['trend']->keys()->last() }}</span></p>
                @endif
            </section>
            @foreach ([['Highest rated', $report['best']], ['Lowest rated', $report['worst']]] as [$title, $list])
                <section class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse ($list as $g)
                            <li class="flex items-baseline justify-between gap-3"><span>{{ $g['label'] }}</span><span class="shrink-0 font-medium">{{ $score($g) }}</span></li>
                        @empty
                            <li class="text-gray-500">Not enough rated questions yet.</li>
                        @endforelse
                    </ul>
                </section>
            @endforeach
        </div>

        {{-- Per question --}}
        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Questions</h2>
            <p class="text-sm text-gray-500">Answers are only added together when a question has the same identifier, type, scale and answer options. Percentages use the number of people who answered that question.</p>
            @foreach ($report['groups'] as $g)
                @php
                    $isRating = $g['type'] === 'rating';
                    $isText = in_array($g['type'], ['text', 'textarea'], true);
                    $r = $isRating ? FeedbackStats::rating($g) : null;
                    $skipped = max(0, $g['asked'] - $g['respondents']);
                @endphp
                <details class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <summary class="flex cursor-pointer flex-wrap items-baseline justify-between gap-2 p-4 text-sm">
                        <span class="font-medium text-gray-950 dark:text-white">{{ $g['label'] }}</span>
                        <span class="text-gray-500">
                            @if ($isRating) {{ $r['average'] !== null ? $score($g).' average' : 'no ratings' }} ·
                            @endif
                            {{ $g['respondents'] }} of {{ $g['asked'] }} answered
                        </span>
                    </summary>
                    <div class="space-y-2 border-t border-gray-200 p-4 text-sm dark:border-white/10">
                        @if ($isRating)
                            <p class="text-gray-500">Median {{ $r['median'] ?? '–' }} · {{ $r['n'] }} ratings · {{ $r['na'] }} "not applicable" · {{ $skipped }} skipped</p>
                            @foreach (array_reverse($r['distribution']) as $row)
                                <div class="flex items-center gap-3"><span class="w-6 text-right">{{ $row['value'] }}</span>
                                    <div class="h-3 flex-1 rounded bg-gray-100 dark:bg-white/10"><div class="h-3 rounded bg-primary-500" style="{{ $bar($row['percent']) }}"></div></div>
                                    <span class="w-24 text-right text-gray-500">{{ $row['count'] }} ({{ $row['percent'] }}%)</span></div>
                            @endforeach
                        @elseif ($isText)
                            <p class="text-gray-500">{{ $g['texts'] }} written answers (redacted ones are not counted).</p>
                            <x-filament::link tag="button" type="button" wire:click="$set('question', '{{ $g['key'] }}')">Show answers below</x-filament::link>
                        @else
                            <p class="text-gray-500">{{ $g['type'] === 'multiselect' ? 'Several options can be selected, so percentages add up to more than 100%. ' : '' }}Percent of the {{ $g['respondents'] }} people who answered · {{ $skipped }} skipped</p>
                            @foreach (FeedbackStats::choices($g) as $row)
                                <div class="flex items-center gap-3"><span class="w-48 truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                    <div class="h-3 flex-1 rounded bg-gray-100 dark:bg-white/10"><div class="h-3 rounded bg-primary-500" style="{{ $bar($row['percent']) }}"></div></div>
                                    <span class="w-24 text-right text-gray-500">{{ $row['count'] }} ({{ $row['percent'] }}%)</span></div>
                            @endforeach
                        @endif
                    </div>
                </details>
            @endforeach
        </section>

        {{-- Comparison between editions --}}
        @if ($surveys->count() > 1)
            <section class="space-y-3">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Comparison between editions</h2>
                <p class="text-sm text-gray-500">Only questions with the same identifier, type, scale and options in at least two selected editions. Ratings show the average and, in brackets, the number of ratings.</p>
                <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-start text-xs text-gray-500 dark:border-white/10">
                                <th class="px-4 py-2 text-start font-medium">Question</th>
                                @foreach ($surveys as $s)<th class="px-4 py-2 text-start font-medium">{{ $s->event->name }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="px-4 py-2 text-gray-500">Responses</td>
                                @foreach ($surveys as $s)<td class="px-4 py-2">{{ $s->responses()->count() }}</td>@endforeach
                            </tr>
                            @forelse ($report['comparable'] as $g)
                                @if ($g['type'] === 'rating')
                                    <tr class="border-b border-gray-100 dark:border-white/5">
                                        <td class="px-4 py-2">{{ $g['label'] }}</td>
                                        @foreach ($surveys as $s)
                                            @php $one = $g['per_survey'][$s->id] ?? null; $res = $one ? FeedbackStats::rating($one) : null; @endphp
                                            <td class="px-4 py-2">{!! $res && $res['average'] !== null ? '<strong>'.number_format($res['average'], 2).'</strong> <span class="text-gray-500">('.$res['n'].')</span>' : '<span class="text-gray-400">–</span>' !!}</td>
                                        @endforeach
                                    </tr>
                                @else
                                    @foreach (FeedbackStats::choices($g) as $option)
                                        <tr class="border-b border-gray-100 dark:border-white/5">
                                            <td class="px-4 py-2">{{ $g['label'] }} <span class="text-gray-500">· {{ $option['label'] }}</span></td>
                                            @foreach ($surveys as $s)
                                                @php $one = $g['per_survey'][$s->id] ?? null; $row = $one ? collect(FeedbackStats::choices($one))->firstWhere('value', $option['value']) : null; @endphp
                                                <td class="px-4 py-2">{!! $row ? $row['percent'].'% <span class="text-gray-500">of '.$one['respondents'].'</span>' : '<span class="text-gray-400">–</span>' !!}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endif
                            @empty
                                <tr><td colspan="{{ $surveys->count() + 1 }}" class="px-4 py-3 text-gray-500">No question is comparable between these editions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Written answers --}}
        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Written answers</h2>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-filament::input.wrapper class="sm:w-72">
                    <x-filament::input.select wire:model.live="question" aria-label="Filter by question">
                        <option value="">All questions</option>
                        @foreach ($report['textQuestions'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper inline-prefix :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass" class="sm:w-72">
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search written answers" aria-label="Search written answers" class="fi-input fi-input-has-inline-prefix" />
                </x-filament::input.wrapper>
            </div>
            <p class="text-xs text-gray-500">Answers are shown as plain text. If one contains personal information, redact it (the text is erased, the fact that it was answered stays) or remove it.</p>
            <ul class="divide-y divide-gray-200 rounded-xl bg-white ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-white/5 dark:ring-white/10">
                @forelse ($report['texts'] as $answer)
                    <li class="flex flex-col gap-2 p-4 text-sm sm:flex-row sm:items-start sm:justify-between" wire:key="answer-{{ $answer->id }}">
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500">{{ $surveys->firstWhere('id', $answer->feedback_survey_id)?->event->name }} · {{ $report['groups']->firstWhere('key', $answer->question_key)['label'] ?? $answer->question_key }} · {{ substr((string) $answer->submitted_on, 0, 10) }}</p>
                            @if ($answer->redacted_at)
                                <p class="mt-1 italic text-gray-500">Redacted</p>
                            @else
                                <p class="mt-1 whitespace-pre-line break-words">{{ $answer->value_text }}</p>
                            @endif
                        </div>
                        @if ($canModerate)
                            <div class="flex shrink-0 gap-2">
                                @unless ($answer->redacted_at)
                                    <x-filament::button size="xs" color="warning" wire:click="redact({{ $answer->id }})" wire:confirm="Erase the text of this answer?">Redact</x-filament::button>
                                @endunless
                                <x-filament::button size="xs" color="danger" wire:click="remove({{ $answer->id }})" wire:confirm="Remove this answer permanently?">Remove</x-filament::button>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="p-4 text-sm text-gray-500">No written answers match.</li>
                @endforelse
            </ul>
            <x-filament::pagination :paginator="$report['texts']" />
        </section>
    @endif
</x-filament-panels::page>
