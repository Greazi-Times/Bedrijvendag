<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            toggle(names) {
                const current = Array.isArray(this.state) ? this.state : []
                const all = names.every((name) => current.includes(name))

                this.state = all ? current.filter((name) => ! names.includes(name)) : [...new Set([...current, ...names])]
            },
        }"
    >
        @include('filament.permissions.matrix', ['mode' => 'edit', 'held' => \App\Support\Access::permissionsOf(auth()->user())])
    </div>
</x-dynamic-component>
