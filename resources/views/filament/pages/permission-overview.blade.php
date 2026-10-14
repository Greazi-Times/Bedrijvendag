@php
    use App\Filament\Resources\Roles\RoleResource;
    use App\Support\Permissions;

    $roles = $this->getRoles();
    $grants = $roles->mapWithKeys(fn ($role) => [
        $role->id => $role->name === Permissions::SUPER_ADMIN ? Permissions::all() : $role->permissions->pluck('name')->all(),
    ]);
@endphp

<x-filament-panels::page>
    <p class="max-w-3xl text-sm text-gray-600 dark:text-gray-400">
        Which role grants what. Permissions are defined in code and only take effect where the application checks them, so they cannot be added here.
        To change a role, open it from the column header.
    </p>

    <div x-data="{ search: '' }" class="flex flex-col gap-6">
        <x-filament::input.wrapper inline-prefix :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass" class="max-w-sm">
            <input type="search" x-model.debounce.150ms="search" placeholder="Filter modules or permissions" class="fi-input fi-input-has-inline-prefix" />
        </x-filament::input.wrapper>

        @foreach (Permissions::sections() as $section => $modules)
            @php
                $searchText = fn (string $module, array $definition): string => mb_strtolower($definition['label'].' '.$definition['description'].' '.implode(' ', $definition['actions']).' '.$module);
            @endphp

            <section x-show="! search || @js(collect($modules)->map(fn ($d, $m) => $searchText($m, $d))->values()).some((text) => text.includes(search.toLowerCase()))">
                <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">{{ $section }}</h3>

                <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <table class="w-full table-fixed text-sm" style="min-width: {{ 22 + $roles->count() * 8 }}rem">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                                <th scope="col" class="w-[22rem] px-4 py-2 text-start">Permission</th>
                                @foreach ($roles as $role)
                                    <th scope="col" class="px-2 py-2 text-center">
                                        @if (RoleResource::canEdit($role))
                                            <x-filament::link :href="RoleResource::getUrl('edit', ['record' => $role])" size="sm">{{ $role->name }}</x-filament::link>
                                        @else
                                            <span class="text-gray-950 dark:text-white">{{ $role->name }}</span>
                                        @endif
                                        <span class="block font-normal">{{ $role->users_count }} {{ str('user')->plural($role->users_count) }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        @foreach ($modules as $module => $definition)
                            <tbody x-show="! search || @js($searchText($module, $definition)).includes(search.toLowerCase())" class="border-t border-gray-200 first-of-type:border-t-0 dark:border-white/10">
                                <tr>
                                    <th scope="rowgroup" colspan="{{ $roles->count() + 1 }}" class="px-4 pt-3 pb-1 text-start font-normal">
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $definition['label'] }}</span>
                                        <span class="ms-2 text-xs text-gray-500 dark:text-gray-400">{{ $definition['description'] }}</span>
                                    </th>
                                </tr>
                                @foreach ($definition['actions'] as $action)
                                    @php($name = "{$module}.{$action}")
                                    <tr>
                                        <th scope="row" class="px-4 py-1.5 ps-8 text-start font-normal text-gray-700 dark:text-gray-300">
                                            {{ Permissions::label($name) }}
                                            <code class="ms-2 text-xs text-gray-400 dark:text-gray-500">{{ $name }}</code>
                                        </th>
                                        @foreach ($roles as $role)
                                            <td class="px-2 py-1.5 text-center">
                                                @if (in_array($name, $grants[$role->id], true))
                                                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::CheckCircle" class="mx-auto size-5 text-success-600 dark:text-success-400" />
                                                    <span class="sr-only">{{ $role->name }} has {{ $name }}</span>
                                                @else
                                                    <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">·</span>
                                                    <span class="sr-only">{{ $role->name }} does not have {{ $name }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        @endforeach
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
