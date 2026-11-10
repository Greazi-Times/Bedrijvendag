@php($enrollment = $getRecord())
@php($invitations = $enrollment->invitations()->latest('id')->get())
@if ($invitations->isEmpty())
    <p class="text-sm text-gray-500">No invitation has been issued yet. Invitations are created when an enrollment is approved.</p>
@else
    <table class="w-full text-sm">
        <thead><tr class="text-left text-gray-500"><th class="py-1 pr-3">Status</th><th class="pr-3">Created</th><th class="pr-3">Expires</th><th class="pr-3">Email queued</th><th>Revoked</th></tr></thead>
        <tbody>
        @foreach ($invitations as $inv)
            @php($status = $inv->revoked_at ? 'revoked' : ($inv->expires_at->isPast() ? 'expired' : 'active'))
            <tr>
                <td class="py-1 pr-3"><span class="rounded px-1.5 text-xs {{ $status === 'active' ? 'bg-success-50 text-success-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($status) }}</span></td>
                <td class="pr-3">{{ $inv->created_at->format('Y-m-d H:i') }}</td>
                <td class="pr-3">{{ $inv->expires_at->format('Y-m-d H:i') }}</td>
                <td class="pr-3">{{ $inv->queued_at?->format('Y-m-d H:i') ?? 'not queued' }}</td>
                <td>{{ $inv->revoked_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="mt-2 text-xs text-gray-500">The link itself is never stored or shown. Sending again issues a replacement and revokes the previous link. “Email queued” means handed to the mail queue; delivery is not tracked here; if a company did not receive it, check Failed jobs and the mail logs, then resend.</p>
@endif
