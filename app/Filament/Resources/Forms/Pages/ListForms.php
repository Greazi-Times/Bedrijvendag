<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New custom form')];
    }

    public function getTabs(): array
    {
        return [
            'global' => Tab::make('Global default forms')->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('system_key')),
            'custom' => Tab::make('Custom forms')->modifyQueryUsing(fn (Builder $query) => $query->whereNull('system_key')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'global';
    }
}
