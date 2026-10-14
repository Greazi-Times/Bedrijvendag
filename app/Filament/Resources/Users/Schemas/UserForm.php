<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\Access;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::default())
                            ->confirmed()
                            ->visibleOn('create'),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false)
                            ->visibleOn('create'),
                    ]),

                Section::make('Roles')
                    ->description('Access comes from roles. You can only assign roles whose permissions you hold yourself.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->hiddenLabel()
                            ->options(fn (): array => Access::assignableRoles(auth()->user())->pluck('name', 'id')->all())
                            ->columns(['sm' => 2, 'lg' => 3])
                            ->disabled(fn (?User $record): bool => (bool) $record?->is(auth()->user()))
                            ->helperText(fn (?User $record): ?string => $record?->is(auth()->user()) ? 'You cannot change your own roles.' : null),
                    ]),

                Section::make('Effective permissions')
                    ->description('Read-only. Hover a check to see which role grants it. Change access through roles.')
                    ->visibleOn('edit')
                    ->collapsible()
                    ->schema([
                        View::make('filament.users.effective-permissions'),
                    ]),
            ]);
    }
}
