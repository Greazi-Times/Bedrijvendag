<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Support\RichText;

/**
 * Turns proposed public-profile values into a review item in the existing profile queue.
 * It never writes to the company; an administrator approves the submission through the existing review.
 */
class CompanyProfileProposer
{
    /**
     * @param  array{website_url?: ?string, description?: ?string, logo_path?: ?string, education_ids?: array, sector_ids?: array}  $proposed  empty values mean "keep what is stored"
     * @param  bool  $supersede  replace a pending proposal (the company asked for it) instead of leaving it alone
     */
    public function propose(Company $company, array $proposed, ?string $contactName, ?string $contactEmail, string $note, bool $supersede): ?CompanyProfileSubmission
    {
        $pending = $company->pendingProfileSubmission()->first();

        if ($pending && ! $supersede) {
            return null; // never override someone else's pending proposal silently
        }

        $company->loadMissing(['educations', 'sectors']);
        $current = [
            'proposed_name' => $company->name,
            'proposed_website_url' => $company->website_url,
            'proposed_description' => $company->description_nl,
            'proposed_logo_path' => $company->logo_path,
            'proposed_education_ids' => $company->educations->pluck('id')->all(),
            'proposed_sector_ids' => $company->sectors->pluck('id')->all(),
        ];
        $proposal = [
            'proposed_name' => $company->name,
            'proposed_website_url' => $proposed['website_url'] ?: $company->website_url,
            'proposed_description' => filled($proposed['description'] ?? null) ? RichText::sanitize($proposed['description']) : $company->description_nl,
            'proposed_logo_path' => ($proposed['logo_path'] ?? null) ?: $company->logo_path,
            'proposed_education_ids' => ($proposed['education_ids'] ?? []) ?: $current['proposed_education_ids'],
            'proposed_sector_ids' => ($proposed['sector_ids'] ?? []) ?: $current['proposed_sector_ids'],
        ];

        if ($this->comparable($proposal) === $this->comparable($current)) {
            return null; // nothing really differs: no review item
        }

        if ($pending) {
            $pending->forceFill(['status' => CompanyProfileSubmission::STATUS_SUPERSEDED])->save();
        }

        return CompanyProfileSubmission::query()->create($proposal + [
            'company_id' => $company->id,
            'status' => CompanyProfileSubmission::STATUS_PENDING,
            'revision' => $pending ? $pending->revision + 1 : 1,
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'attention_note' => $pending?->attention_note ?? $note,
            'submitted_at' => now(),
        ]);
    }

    /** Descriptions compare as plain text: markup-only differences are not changes. */
    private function comparable(array $values): array
    {
        $text = fn (?string $v): ?string => filled($v) ? trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $v))) ?? '') : null;

        return array_map(fn ($v) => is_array($v) ? collect($v)->map(fn ($i) => (int) $i)->sort()->values()->all() : $text($v), $values);
    }
}
