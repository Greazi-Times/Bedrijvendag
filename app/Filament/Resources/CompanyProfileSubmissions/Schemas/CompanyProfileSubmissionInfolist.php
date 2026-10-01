<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Schemas;

use App\Models\CompanyProfileSubmission;
use App\Support\ProfileDiff;
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
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->status !== CompanyProfileSubmission::STATUS_PENDING && $record->comparison_snapshot === null)
                    ->description('This older submission has no saved original values. Only the submitted profile is available.')
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
            ]);
    }
}
