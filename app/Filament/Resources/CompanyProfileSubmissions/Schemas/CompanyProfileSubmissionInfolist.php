<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Schemas;

use App\Models\CompanyProfileSubmission;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyProfileSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Current company'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                CompanyProfileSubmission::STATUS_APPROVED => 'success',
                                CompanyProfileSubmission::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('contact_name')
                            ->placeholder('No contact name'),
                        TextEntry::make('contact_email')
                            ->placeholder('No contact email'),
                        TextEntry::make('submitted_at')
                            ->dateTime(),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet'),
                    ]),
                Section::make('Proposed public profile')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('proposed_name')
                            ->label('Company name'),
                        TextEntry::make('proposed_website_url')
                            ->label('Website')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('No website'),
                        ImageEntry::make('proposed_logo_path')
                            ->label('Logo')
                            ->disk('public')
                            ->height(120)
                            ->placeholder('No logo'),
                        TextEntry::make('proposed_description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description'),
                        TextEntry::make('proposedEducationNames')
                            ->label('Educations')
                            ->getStateUsing(fn (CompanyProfileSubmission $record): string => $record->proposedEducationNames())
                            ->columnSpanFull(),
                        TextEntry::make('proposedSectorNames')
                            ->label('Sectors')
                            ->getStateUsing(fn (CompanyProfileSubmission $record): string => $record->proposedSectorNames())
                            ->columnSpanFull(),
                        TextEntry::make('proposed_new_sector_names')
                            ->label('New sectors to review')
                            ->badge()
                            ->placeholder('No new sectors proposed')
                            ->columnSpanFull(),
                    ]),
                Section::make('Review')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('Not reviewed yet'),
                        TextEntry::make('review_note')
                            ->placeholder('No note')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
