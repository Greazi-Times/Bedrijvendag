{{--
    Modules as rows, actions as columns, grouped like the sidebar.
    $mode 'edit': checkboxes bound to the surrounding Alpine `state` array; $held lists what the editor may change.
    $mode 'read': $granted maps each held permission to the roles that grant it.
--}}
@php
    use App\Support\Permissions;

    $edit = $mode === 'edit';
    $standard = Permissions::STANDARD_ACTIONS;
    $allowedToChange = fn (string $name): bool => $edit && $held->contains($name);
@endphp

<div x-data="{ search: '' }" class="flex flex-col gap-6">
    <x-filament::input.wrapper inline-prefix :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass" class="max-w-sm">
        <input type="search" x-model.debounce.150ms="search" placeholder="Filter modules or permissions" class="fi-input fi-input-has-inline-prefix" />
    </x-filament::input.wrapper>

    @foreach (Permissions::sections() as $section => $modules)
        @php
            $rows = collect($modules)->map(function (array $definition, string $module): array {
                $names = collect($definition['actions'])->map(fn (string $action): string => "{$module}.{$action}");

                return [
                    'module' => $module,
                    'definition' => $definition,
                    'names' => $names,
                    'search' => mb_strtolower(implode(' ', [$definition['label'], $definition['description'], ...$names, ...$names->map(fn ($name) => Permissions::label($name))])),
                ];
            });
            $sectionChangeable = $rows->pluck('names')->flatten()->filter($allowedToChange)->values();
        @endphp

        <section x-show="! search || @js($rows->pluck('search')->all()).some((text) => text.includes(search.toLowerCase()))">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $section }}</h3>
                @if ($sectionChangeable->isNotEmpty())
                    <x-filament::link tag="button" type="button" size="sm" x-on:click="toggle({{ Js::from($sectionChangeable) }})">
                        Toggle section
                    </x-filament::link>
                @endif
            </div>

            <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <table class="w-full min-w-[44rem] table-fixed text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            <th scope="col" class="px-4 py-2 text-start">Module</th>
                            @foreach ($standard as $action)
                                <th scope="col" class="w-20 px-2 py-2 text-center">{{ Permissions::label("x.{$action}") }}</th>
                            @endforeach
                            <th scope="col" class="w-48 px-4 py-2 text-start">Other</th>
                            @if ($edit)
                                <th scope="col" class="w-16 px-2 py-2 text-center"><span class="sr-only">Whole row</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($rows as $row)
                            @php
                                $label = $row['definition']['label'];
                                $other = $row['names']->reject(fn ($name) => in_array(str($name)->after('.')->toString(), $standard, true));
                                $rowChangeable = $row['names']->filter($allowedToChange)->values();
                            @endphp
                            <tr x-show="! search || @js($row['search']).includes(search.toLowerCase())" class="align-top">
                                <th scope="row" class="px-4 py-3 text-start font-normal">
                                    <span class="block font-medium text-gray-950 dark:text-white">{{ $label }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $row['definition']['description'] }}</span>
                                </th>
                                @foreach ($standard as $action)
                                    @php($name = "{$row['module']}.{$action}")
                                    <td class="px-2 py-3 text-center">
                                        @if ($row['names']->contains($name))
                                            @include('filament.permissions.matrix-cell', ['name' => $name, 'text' => "{$label}: ".Permissions::label($name)])
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600" aria-label="Not applicable">–</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1.5">
                                        @foreach ($other as $name)
                                            <label class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                                @include('filament.permissions.matrix-cell', ['name' => $name, 'text' => "{$label}: ".Permissions::label($name)])
                                                <span>{{ Permissions::label($name) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                @if ($edit)
                                    <td class="px-2 py-3 text-center">
                                        @if ($rowChangeable->count() > 1)
                                            <x-filament::link tag="button" type="button" size="sm" x-on:click="toggle({{ Js::from($rowChangeable) }})" :aria-label="'Toggle all for '.$label">
                                                All
                                            </x-filament::link>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>
