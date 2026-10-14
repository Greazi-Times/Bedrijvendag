<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Every value is rendered as escaped text; nothing logged is ever output as HTML. */
class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $timezone = config('audit.display_timezone');

        return $schema
            ->columns(2)
            ->components([
                Section::make('Event')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('Event ID'),
                        TextEntry::make('created_at')
                            ->label('Time')
                            ->dateTime('Y-m-d H:i:s T', $timezone)
                            ->helperText(fn (AuditLog $record): string => $record->created_at->utc()->format('Y-m-d H:i:s').' UTC'),
                        TextEntry::make('log_name')->label('Category')->formatStateUsing(fn (?string $state): string => AuditLogResource::categoryLabel($state)),
                        TextEntry::make('event')->fontFamily('mono'),
                        TextEntry::make('severity')->badge()->color(fn (string $state): string => AuditLogResource::severityColor($state)),
                        TextEntry::make('result')->badge()->color(fn (string $state): string => AuditLogResource::resultColor($state)),
                        TextEntry::make('description')->columnSpanFull(),
                    ]),

                Section::make('Actor')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('actor_label')
                            ->label('Name')
                            ->placeholder(fn (AuditLog $record): string => ucfirst($record->actor_type))
                            ->url(fn (AuditLog $record): ?string => self::userUrl($record->causer)),
                        TextEntry::make('actor_type')->label('Type')->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('causer_id')->label('User ID')->placeholder('-'),
                        TextEntry::make('actor_email')
                            ->label('Email')
                            ->state(fn (AuditLog $record): ?string => $record->causer?->getAttribute('email'))
                            ->placeholder('Account no longer exists')
                            ->visible(fn (AuditLog $record): bool => $record->causer_id && (auth()->user()?->can('users.view') ?? false)),
                    ]),

                Section::make('Request')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('ip_address')->label('IP address')->placeholder('-')->copyable()
                            ->url(fn (AuditLog $record): ?string => $record->ip_address ? AuditLogResource::filteredUrl(['ip_address' => ['ip_address' => $record->ip_address]]) : null),
                        TextEntry::make('http_method')->label('Method')->placeholder('-'),
                        TextEntry::make('route_name')->label('Route')->placeholder('-')->fontFamily('mono'),
                        TextEntry::make('path')->placeholder('-')->fontFamily('mono'),
                        TextEntry::make('request_id')->label('Request ID')->placeholder('-')->fontFamily('mono')->copyable()
                            ->url(fn (AuditLog $record): ?string => $record->request_id ? AuditLogResource::getUrl('index', ['search' => $record->request_id]) : null),
                        TextEntry::make('user_agent')->label('User agent')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Affected resource')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subject_type')->label('Type')->placeholder('-')
                            ->formatStateUsing(fn (?string $state): ?string => AuditLogResource::resourceTypeLabel($state)),
                        TextEntry::make('subject_id')->label('ID')->placeholder('-'),
                        TextEntry::make('subject_label')
                            ->label('Name')
                            ->placeholder('-')
                            ->url(fn (AuditLog $record): ?string => AuditLogResource::subjectUrl($record))
                            ->helperText(fn (AuditLog $record): ?string => $record->subject_id && ! $record->subject ? 'This record has since been deleted.' : null),
                    ]),

                Section::make('Changes')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('properties')->hiddenLabel()->view('filament.audit.changes'),
                    ]),
            ]);
    }

    private static function userUrl(mixed $user): ?string
    {
        return $user instanceof User && UserResource::canEdit($user) ? UserResource::getUrl('edit', ['record' => $user]) : null;
    }
}
