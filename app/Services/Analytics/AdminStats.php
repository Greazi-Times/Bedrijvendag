<?php

namespace App\Services\Analytics;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\CompanyProfileSubmission;
use App\Models\Enrollment;
use App\Models\EnrollmentContactChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Work that needs an administrator. Each item names the permission needed to see it; items the user may not see are
 * never counted, so a limited role learns nothing from the figures. Items link to the existing resources.
 */
class AdminStats
{
    /**
     * @return list<array{key: string, label: string, count: int, color: string, url: ?string, tooltip: string, group: string}>
     */
    public function items(Scope $scope, User $user): array
    {
        $event = $scope->event;
        $eventId = $event?->id;
        $enrollments = fn () => Enrollment::query()->when($eventId, fn ($q) => $q->where('event_id', $eventId));
        $filter = fn (array $extra = []) => array_filter(['event_id' => ['value' => $eventId], ...$extra], fn ($f) => $f !== ['value' => null]);
        $list = fn (string $resource, array $filters = []) => $resource::getUrl('index', $filters === [] ? [] : ['filters' => $filters]);
        $items = [];
        $add = function (string $permission, string $key, string $label, \Closure $count, string $color, ?string $url, string $tooltip, string $group = 'Inschrijvingen') use (&$items, $user): void {
            if ($user->can($permission)) {
                $n = (int) $count();
                $items[] = ['key' => $key, 'label' => $label, 'count' => $n, 'color' => $n > 0 ? $color : 'gray', 'url' => $url, 'tooltip' => $tooltip, 'group' => $group];
            }
        };

        $add('enrollments.view', 'pending', 'Inschrijvingen wachten op goedkeuring', fn () => $enrollments()->where('status', Enrollment::STATUS_PENDING)->count(), 'warning',
            $list(EnrollmentResource::class, $filter(['status' => ['value' => 'pending']])), 'Nieuwe inschrijvingen die nog niet zijn beoordeeld.');
        $add('company-profile-submissions.view', 'profile', 'Profielwijzigingen wachten op goedkeuring', fn () => CompanyProfileSubmission::query()->where('status', CompanyProfileSubmission::STATUS_PENDING)->count(), 'warning',
            \App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource::getUrl('index'), 'Over alle edities: voorgestelde bedrijfsprofielwijzigingen in afwachting van beoordeling.', 'Bedrijven');
        $add('enrollments.view', 'info_missing', 'Goedgekeurd, informatieformulier ontbreekt', fn () => $enrollments()->where('status', Enrollment::STATUS_APPROVED)->where('info_status', '!=', Enrollment::INFO_SUBMITTED)->count(), 'warning',
            $list(EnrollmentResource::class, $filter(['status' => ['value' => 'approved'], 'info_status' => ['value' => 'awaiting']])), 'Goedgekeurde bedrijven die het tweede formulier nog niet hebben ingevuld.');
        $add('enrollments.view', 'contact_changes', 'Niet-geverifieerde contactwijzigingen', fn () => EnrollmentContactChange::query()->whereNull('verified_at')->whereNull('superseded_at')->where('expires_at', '>', now())
            ->when($eventId, fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('event_id', $eventId)))->count(), 'info',
            $list(EnrollmentResource::class, $filter()), 'Een nieuw contactadres is opgegeven maar nog niet bevestigd; het wordt niet gebruikt voor e-mail.');
        $add('enrollments.view', 'expired_invitations', 'Verlopen uitnodigingen', fn () => $enrollments()->where('status', Enrollment::STATUS_APPROVED)->where('info_status', Enrollment::INFO_AWAITING)
            ->whereHas('invitations')->whereDoesntHave('invitations', fn ($i) => $i->whereNull('revoked_at')->where('expires_at', '>', now()))->count(), 'danger',
            $list(EnrollmentResource::class, $filter(['info_status' => ['value' => 'awaiting']])), 'Het bedrijf wacht op het informatieformulier maar heeft geen geldige uitnodigingslink meer.');
        $add('enrollments.view', 'resent', 'Opnieuw verzonden uitnodigingen', fn () => $enrollments()->whereHas('invitations', null, '>', 1)->count(), 'gray',
            $list(EnrollmentResource::class, $filter()), 'Inschrijvingen waarvoor meer dan één uitnodiging is uitgegeven.');

        if ($event) {
            $add('event-stands.view', 'no_stand', 'Goedgekeurde bedrijven zonder stand', fn () => count(app(EventPlanningStats::class)->stands($event->id)['approved_without_stand'] ?? []), 'warning',
                EventStandResource::getUrl('index'), 'Goedgekeurde bedrijven van deze editie die nog niet op de plattegrond staan.', 'Plattegrond');
            $add('event-stands.view', 'stand_warnings', 'Waarschuwingen bij standtoewijzing', fn () => app(EventPlanningStats::class)->stands($event->id)['warnings'], 'danger',
                EventStandResource::getUrl('index'), 'Stands van een lopende of toekomstige editie waarvan het bedrijf geen goedgekeurde inschrijving heeft.', 'Plattegrond');
        }

        $add('security.view', 'failed_jobs', 'Mislukte wachtrijtaken', fn () => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0, 'danger',
            null, 'Mislukte achtergrondtaken, bijvoorbeeld e-mails die niet zijn verzonden.', 'Systeem');

        return $items;
    }
}
