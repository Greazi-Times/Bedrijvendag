@php
    $enrollment = $getRecord();
    $company = $enrollment->company ?? $enrollment->selectedCompany;
    $rows = \App\Support\EnrollmentPresenter::comparison($enrollment, $company);
@endphp
@if (! $company)
    <p class="text-sm text-gray-500">New company: nothing to compare. On approval the company is created from the submitted answers.</p>
@else
    <p class="mb-2 text-sm text-gray-500">Against <strong>{{ $company->name }}</strong>. Approving never overwrites these values; differences are sent to the profile review queue.</p>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-gray-500"><th class="py-1 pr-3">Field</th><th class="pr-3">Current</th><th>Submitted</th></tr></thead>
        <tbody>
        @foreach ($rows as $r)
            <tr class="{{ $r['differs'] ? 'bg-warning-50 dark:bg-warning-950' : '' }}">
                <td class="py-1 pr-3 font-medium">{{ $r['field'] }}</td>
                <td class="pr-3 align-top">{{ \Illuminate\Support\Str::limit($r['current'], 200) }}</td>
                <td class="align-top">{{ \Illuminate\Support\Str::limit($r['submitted'], 200) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
