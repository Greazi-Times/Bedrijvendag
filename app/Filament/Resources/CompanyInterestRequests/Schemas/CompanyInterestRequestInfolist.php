<?php

namespace App\Filament\Resources\CompanyInterestRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInterestRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company interest for upcoming edition')
                ->columns(2)
                ->schema([
                    TextEntry::make('event.name')
                        ->label('Edition')
                        ->placeholder('No upcoming edition'),
                    TextEntry::make('created_at')
                        ->label('Received')
                        ->dateTime(),
                    TextEntry::make('company_name')
                        ->label('Company'),
                    TextEntry::make('website_url')
                        ->label('Website')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->placeholder('No website'),
                    TextEntry::make('contact_name')
                        ->label('Contact person'),
                    TextEntry::make('contact_email')
                        ->label('Email')
                        ->copyable(),
                    TextEntry::make('message')
                        ->label('Question or additional information')
                        ->placeholder('No message')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
