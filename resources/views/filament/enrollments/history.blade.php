@php($enrollment = $getRecord())
<ul class="space-y-1 text-sm">
    <li>Submitted {{ $enrollment->submitted_at->format('Y-m-d H:i') }}</li>
    @if ($enrollment->reviewed_at)
        <li>{{ ucfirst($enrollment->status) }} {{ $enrollment->reviewed_at->format('Y-m-d H:i') }} by {{ $enrollment->reviewer?->name ?? 'unknown' }}</li>
    @endif
    @foreach ($enrollment->invitations()->latest()->get() as $inv)
        <li>Invitation: expires {{ $inv->expires_at->format('Y-m-d') }}, {{ $inv->revoked_at ? 'revoked' : ($inv->queued_at ? 'queued '.$inv->queued_at->format('Y-m-d H:i') : 'not queued') }}</li>
    @endforeach
</ul>
