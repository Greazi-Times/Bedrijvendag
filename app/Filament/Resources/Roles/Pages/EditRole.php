<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Support\Access;
use App\Support\Permissions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Role $record): bool => in_array($record->name, Permissions::SYSTEM_ROLES, true))
                ->modalDescription(fn (Role $record): string => "Users with this role lose its permissions. {$record->users()->count()} user(s) have it now.")
                ->using(function (Role $record): bool {
                    Access::deleteRole(auth()->user(), $record);

                    return true;
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->getRecord()->permissions
            ->pluck('name')
            ->groupBy(fn (string $name): string => RoleForm::stateKey(Permissions::module($name)))
            ->map->values()
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        return Access::saveRole(
            auth()->user(),
            $record,
            $data['name'] ?? $record->name,
            collect($data['permissions'] ?? [])->flatten()->all(),
        );
    }
}
