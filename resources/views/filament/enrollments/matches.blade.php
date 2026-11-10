@php
    $enrollment = $getRecord();
    $matches = \App\Support\EnrollmentPresenter::matches($enrollment);
    $selected = $enrollment->selectedCompany;
@endphp
<div class="space-y-2 text-sm">
    <p class="text-gray-500">Matches are hints only. A similar name does not prove the applicant represents that company.</p>
    @if ($selected)
        <p>Applicant selected: <strong>{{ $selected->name }}</strong> (unverified claim)</p>
    @else
        <p>Applicant entered a new company name.</p>
    @endif
    @forelse ($matches['exact'] as $c)
        <div>• <strong>{{ $c->name }}</strong> <span class="rounded bg-danger-50 px-1.5 text-xs text-danger-700">exact match</span> @if ($c->website_url) <span class="text-gray-500">{{ $c->website_url }}</span> @endif</div>
    @empty
    @endforelse
    @foreach ($matches['similar'] as $c)
        <div>• {{ $c->name }} <span class="rounded bg-warning-50 px-1.5 text-xs text-warning-700">possible match</span> @if ($c->website_url) <span class="text-gray-500">{{ $c->website_url }}</span> @endif</div>
    @endforeach
    @if ($matches['exact']->isEmpty() && $matches['similar']->isEmpty())
        <div class="text-gray-500">No similar companies found.</div>
    @endif
    @php
        $matcher = app(\App\Services\CompanyMatcher::class);
        [$base] = $matcher->parts($enrollment->company_name);
        $others = \App\Models\Enrollment::where('event_id', $enrollment->event_id)->where('id', '!=', $enrollment->id)->get()
            ->filter(fn ($o) => $o->name_key === $enrollment->name_key || strtolower($o->contact_email) === strtolower($enrollment->contact_email) || ($base !== '' && $matcher->parts($o->company_name)[0] === $base))
            ->map(fn ($o) => '#'.$o->id.' '.$o->company_name.' ('.$o->status.', '.$o->contact_email.')'.($o->name_key === $enrollment->name_key ? ' same name' : (strtolower($o->contact_email) === strtolower($enrollment->contact_email) ? ' same contact email' : ' similar name')));
    @endphp
    @if ($others->isNotEmpty())
        <div class="rounded bg-warning-50 p-2 text-warning-700">Other enrollments for this edition that may be the same company: {{ $others->implode('; ') }}</div>
    @endif
</div>
