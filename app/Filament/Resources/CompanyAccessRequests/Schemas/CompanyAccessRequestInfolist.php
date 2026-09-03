<?php

namespace App\Filament\Resources\CompanyAccessRequests\Schemas;

use App\Models\CompanyAccessRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyAccessRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === CompanyAccessRequest::TYPE_NEW ? 'New company' : 'Existing company')
                            ->color(fn (string $state): string => $state === CompanyAccessRequest::TYPE_NEW ? 'info' : 'gray'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                CompanyAccessRequest::STATUS_APPROVED => 'success',
                                CompanyAccessRequest::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('company.name')
                            ->label('Matched company')
                            ->placeholder('No company linked yet'),
                        TextEntry::make('company_name')
                            ->label('Requested company name')
                            ->placeholder('No name provided'),
                        TextEntry::make('website_url')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('No website'),
                        TextEntry::make('submitted_at')
                            ->dateTime(),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('contact_name'),
                        TextEntry::make('contact_email')
                            ->copyable()
                            ->copyMessage('Email copied'),
                        TextEntry::make('message')
                            ->placeholder('No message')
                            ->columnSpanFull(),
                    ]),
                Section::make('Private verification link')
                    ->schema([
                        TextEntry::make('verificationUrl')
                            ->label('Verification link')
                            ->getStateUsing(fn (CompanyAccessRequest $record): ?string => $record->verificationUrl())
                            ->copyable()
                            ->copyMessage('Verification link copied')
                            ->placeholder('Approve the request first to create or link the company.'),
                    ]),
                Section::make('Review')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('Not reviewed yet'),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet'),
                        TextEntry::make('review_note')
                            ->placeholder('No note')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
