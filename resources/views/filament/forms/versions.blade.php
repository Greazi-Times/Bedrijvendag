<div class="space-y-3 text-sm">
    @forelse ($form->versions as $version)
        <details class="rounded-lg border border-gray-200 p-3 dark:border-white/10" @if ($loop->first) open @endif>
            <summary class="flex cursor-pointer flex-wrap items-center gap-x-4 gap-y-1">
                <span class="font-semibold">Version {{ $version->version }}</span>
                @if ($loop->first)<span class="rounded bg-success-50 px-1.5 text-xs text-success-700">current</span>@endif
                <span class="text-gray-500">{{ $version->published_at->format('Y-m-d H:i') }}{{ $version->published_by ? ' by '.(\App\Models\User::find($version->published_by)?->name ?? 'a former user') : '' }}</span>
                <span class="text-gray-500">{{ collect($version->schema['sections'] ?? [])->sum(fn ($s) => count($s['fields'] ?? [])) }} questions</span>
            </summary>
            <div class="mt-3">@include('filament.forms.preview', ['schema' => $version->schema, 'problems' => []])</div>
        </details>
    @empty
        <p class="text-gray-500">Not published yet.</p>
    @endforelse
    <p class="text-xs text-gray-500">Published versions are never changed or deleted. Submissions keep the version they were answered against.</p>
</div>
