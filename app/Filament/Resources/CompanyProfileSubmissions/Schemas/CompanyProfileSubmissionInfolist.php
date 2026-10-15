<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Schemas;

use App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource;
use App\Models\CompanyProfileSubmission;
use App\Support\ProfileDiff;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyProfileSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Earlier version')
                    ->description('The company sent a newer update after this one. This version is kept as history and cannot be approved or rejected.')
                    ->icon('heroicon-o-clock')
                    ->iconColor('gray')
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->status === CompanyProfileSubmission::STATUS_SUPERSEDED)
                    ->schema([
                        TextEntry::make('latest_version')
                            ->hiddenLabel()
                            ->getStateUsing(fn (CompanyProfileSubmission $record): string => $record->company?->pendingProfileSubmission()->exists()
                                ? 'Review the latest version'
                                : 'A later version has already been reviewed. See the history below.')
                            ->url(fn (CompanyProfileSubmission $record): ?string => ($latest = $record->company?->pendingProfileSubmission()->first())
                                ? CompanyProfileSubmissionResource::getUrl('view', ['record' => $latest])
                                : null)
                            ->color(fn (CompanyProfileSubmission $record): string => $record->company?->pendingProfileSubmission()->exists() ? 'primary' : 'gray'),
                    ]),
                Section::make('Check the history before approving')
                    ->description(fn (CompanyProfileSubmission $record): ?string => $record->attention_note)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->isPending() && filled($record->attention_note)),
                Section::make('Submission')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Current company'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => CompanyProfileSubmission::STATUS_LABELS[$state] ?? ucfirst($state))
                            ->color(fn (string $state): string => CompanyProfileSubmission::STATUS_COLORS[$state] ?? 'gray'),
                        TextEntry::make('contact_name')
                            ->placeholder('No contact name'),
                        TextEntry::make('contact_email')
                            ->placeholder('No contact email'),
                        TextEntry::make('submitted_at')
                            ->label('Last updated')
                            ->dateTime(),
                        TextEntry::make('revision')
                            ->label('Updates in this review')
                            ->helperText('How many times the company sent changes before this was reviewed.'),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet'),
                    ]),
                Section::make('Proposed public profile')
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->status !== CompanyProfileSubmission::STATUS_PENDING && $record->comparison_snapshot === null)
                    ->description(fn (CompanyProfileSubmission $record): string => $record->status === CompanyProfileSubmission::STATUS_SUPERSEDED
                        ? 'The values the company sent in this earlier version. Compare the latest version to see what will change.'
                        : 'This older submission has no saved original values. Only the submitted profile is available.')
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
                            ->html()
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
                Section::make('Profile changes')
                    ->columnSpanFull()
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->status === CompanyProfileSubmission::STATUS_PENDING || $record->comparison_snapshot !== null)
                    ->description(fn (CompanyProfileSubmission $record): string => $record->status === CompanyProfileSubmission::STATUS_PENDING
                        ? 'Red / strikethrough = removed. Green / underline = added. Changes are applied only after approval.'
                        : 'Saved comparison at review. Red / strikethrough = removed. Green / underline = added.')
                    ->schema(function (CompanyProfileSubmission $record): array {
                        $sections = [];

                        foreach ($record->profileComparison() as $field => $change) {
                            $highlighted = $field === 'logo' ? $change : ProfileDiff::render($field, $change);
                            $entries = [];
                            foreach (['before', 'after'] as $side) {
                                $label = $side === 'before'
                                    ? ($record->status === CompanyProfileSubmission::STATUS_PENDING ? 'Current' : 'Before review')
                                    : ($record->status === CompanyProfileSubmission::STATUS_APPROVED ? 'Approved' : 'Proposed');
                                $entry = $field === 'logo'
                                    ? ImageEntry::make("comparison_{$field}_{$side}")->disk('public')->height(120)
                                    : TextEntry::make("comparison_{$field}_{$side}");

                                $entries[] = $entry->label($label)
                                    ->getStateUsing(fn () => trim(strip_tags((string) $highlighted[$side])) === '' ? null : $highlighted[$side])
                                    ->placeholder('Not provided');
                            }

                            if ($field === 'description') {
                                $entries[] = Section::make('Formatted description preview')
                                    ->description('Expand to inspect formatting and links as well as the text changes above.')
                                    ->columnSpanFull()->columns(2)->collapsible()->collapsed()
                                    ->schema([
                                        TextEntry::make('formatted_before')->label('Before')->getStateUsing(fn () => $change['before'])->html()->placeholder('Not provided'),
                                        TextEntry::make('formatted_after')->label('After')->getStateUsing(fn () => $change['after'])->html()->placeholder('Not provided'),
                                    ]);
                            }

                            $sections[] = Section::make(match ($field) {
                                'name' => 'Company name',
                                'educations' => 'Study programmes',
                                default => ucfirst($field),
                            })
                                ->description($change['changed'] ? 'Changed' : 'Unchanged')
                                ->icon($change['changed'] ? 'heroicon-o-pencil-square' : 'heroicon-o-check')
                                ->columns(2)
                                ->collapsible()
                                ->collapsed(! $change['changed'])
                                ->schema($entries);
                        }

                        return $sections;
                    }),
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
                Section::make('Submission history')
                    ->description('Every version this company sent, newest first. Earlier versions are read-only.')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('history')
                            ->hiddenLabel()
                            ->view('filament.resources.company-profile-submissions.history-entry'),
                    ]),
            ]);
    }
}
