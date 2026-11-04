<?php

namespace App\Filament\Resources\CompanyAccessRequests\Pages;

use App\Filament\Resources\CompanyAccessRequests\CompanyAccessRequestResource;
use App\Filament\Resources\CompanyAccessRequests\ReviewActions;
use App\Models\Company;
use App\Models\CompanyAccessRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ViewCompanyAccessRequest extends ViewRecord
{
    protected static string $resource = CompanyAccessRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->authorize('review')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->record->status === CompanyAccessRequest::STATUS_PENDING)
                ->fillForm(fn (): array => [
                    'message' => $this->record->type === CompanyAccessRequest::TYPE_EXISTING ? self::EXISTING_COMPANY_MESSAGE : null,
                ])
                ->form([
                    Textarea::make('message')
                        ->label('Message to the requester (optional, included in the email)')
                        ->rows(4)
                        ->maxLength(2000),
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(fn (array $data) => $this->approveRequest($data)),
            Action::make('approveAndLink')
                ->authorize('review')
                ->label('Approve and Link Existing Company')
                ->color('success')
                ->icon('heroicon-o-link')
                ->visible(fn (): bool => $this->record->status === CompanyAccessRequest::STATUS_PENDING
                    && $this->record->type === CompanyAccessRequest::TYPE_NEW)
                ->modalDescription('The requested company already exists? Link this request to it instead of creating a new company. The requester receives the existing company\'s personal profile link.')
                ->fillForm(fn (): array => [
                    'company_id' => Company::matchesForName((string) $this->record->company_name)['exact']->first()?->id,
                    'message' => self::EXISTING_COMPANY_MESSAGE,
                ])
                ->form([
                    Select::make('company_id')
                        ->label('Existing company')
                        ->helperText('Requested name: '.$this->record->company_name)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => $this->companyOptions($this->record->company_name))
                        ->getSearchResultsUsing(fn (string $search): array => $this->companyOptions($search))
                        ->getOptionLabelUsing(fn ($value): ?string => $this->companyLabel(Company::query()->find($value)))
                        ->rule(Rule::exists('companies', 'id')),
                    Textarea::make('message')
                        ->label('Message to the requester (optional, included in the email)')
                        ->rows(4)
                        ->maxLength(2000),
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->rows(3),
                ])
                ->modalSubmitActionLabel('Approve and link')
                ->requiresConfirmation()
                ->action(fn (array $data) => $this->approveRequest($data, (int) $data['company_id'])),
            ReviewActions::configureReject(Action::make('reject')->label('Reject'))
                ->visible(fn (): bool => $this->record->status === CompanyAccessRequest::STATUS_PENDING)
                ->action(fn (array $data) => ReviewActions::reject($this->record, $data)),
        ];
    }

    private const EXISTING_COMPANY_MESSAGE = 'Jullie bedrijf stond al in ons systeem. We hebben je aanvraag gekoppeld aan het bestaande bedrijfsprofiel en goedgekeurd. Je kunt nu de persoonlijke link hieronder gebruiken om de bedrijfsinformatie te beheren.';

    /** @param array<string, mixed> $data */
    private function approveRequest(array $data, ?int $linkCompanyId = null): void
    {
        try {
            $approved = $this->record->approve($data['review_note'] ?? null, $data['message'] ?? null, $linkCompanyId);
        } catch (ValidationException $exception) {
            Notification::make()->title('Approval blocked')->body(collect($exception->errors())->flatten()->implode(' '))->danger()->send();

            return;
        }

        ReviewActions::feedback($this->record, $approved, 'approved');
    }

    /** @return array<int, string> */
    private function companyOptions(string $search): array
    {
        $matches = Company::matchesForName($search);
        $term = '%'.addcslashes(trim($search), '%_\\').'%';

        return Company::query()
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhereIn('id', $matches['exact']->merge($matches['similar'])->pluck('id')))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->mapWithKeys(fn (Company $company): array => [$company->id => $this->companyLabel($company)])
            ->all();
    }

    private function companyLabel(?Company $company): ?string
    {
        return $company ? collect([$company->name, $company->website_url, $company->profile_contact_email ? 'contact: '.$company->profile_contact_email : null])->filter()->implode(' · ') : null;
    }
}
