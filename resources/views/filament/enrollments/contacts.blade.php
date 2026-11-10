@php
    $enrollment = $getRecord();
    $contact = $enrollment->operationalContact();
    $history = $enrollment->contactChanges()->latest('id')->get();
@endphp
<div class="space-y-3 text-sm">
    <table class="w-full">
        <tbody>
            <tr>
                <td class="w-1/3 py-1 pr-3 text-gray-500">Original enrollment contact</td>
                <td>{{ $enrollment->contact_name ? $enrollment->contact_name.' ' : '' }}&lt;{{ $enrollment->contact_email }}&gt; <span class="rounded bg-gray-100 px-1.5 text-xs text-gray-600">trusted, never changed</span></td>
            </tr>
            <tr>
                <td class="py-1 pr-3 text-gray-500">Current event contact</td>
                <td>
                    {{ $contact['name'] ? $contact['name'].' ' : '' }}&lt;{{ $contact['email'] }}&gt;
                    @if ($contact['source'] === 'verified')
                        <span class="rounded bg-success-50 px-1.5 text-xs text-success-700">verified</span>
                    @else
                        <span class="rounded bg-gray-100 px-1.5 text-xs text-gray-600">same as original</span>
                    @endif
                    <span class="block text-xs text-gray-500">All messages go here. Only a verified address can replace the original.</span>
                </td>
            </tr>
            <tr>
                <td class="py-1 pr-3 text-gray-500">Proposed contact</td>
                <td>
                    @if ($contact['pending'])
                        {{ $contact['pending']['name'] ? $contact['pending']['name'].' ' : '' }}&lt;{{ $contact['pending']['email'] }}&gt;
                        <span class="rounded bg-warning-50 px-1.5 text-xs text-warning-700">unverified</span>
                        <span class="block text-xs text-gray-500">Verification email {{ $contact['pending']['queued'] ? 'queued (delivery is not tracked)' : 'not queued' }}; the link expires {{ $contact['pending']['expires_at']->format('Y-m-d H:i') }}. Receives nothing else until confirmed.</span>
                    @else
                        <span class="text-gray-500">None pending</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
    @if ($history->isNotEmpty())
        <details>
            <summary class="cursor-pointer text-gray-500">History ({{ $history->count() }})</summary>
            <ul class="mt-1 space-y-0.5 text-xs text-gray-600">
                @foreach ($history as $c)
                    <li>{{ $c->created_at->format('Y-m-d H:i') }}: {{ $c->name ? $c->name.' ' : '' }}&lt;{{ $c->email }}&gt; —
                        {{ $c->verified_at ? 'verified ('.($c->verified_by === 'email' ? 'by email link' : 'known address').')' : ($c->superseded_at ? 'replaced before verification' : ($c->expires_at?->isPast() ? 'expired' : 'awaiting verification')) }}{{ $c->verified_at && $c->superseded_at ? ', later replaced' : '' }}</li>
                @endforeach
            </ul>
        </details>
    @endif
</div>
