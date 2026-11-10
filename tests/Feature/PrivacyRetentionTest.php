<?php

use App\Models\BorrelEnrollment;
use App\Models\Enrollment;
use App\Models\Event;
use App\Support\Privacy\EmailCrypto;
use App\Support\Privacy\PrivacyRetention;
use App\Support\Privacy\SensitiveAnswers;
use Illuminate\Support\Facades\DB;

function cleanup(bool $dry = false): array
{
    return (new PrivacyRetention)->run($dry);
}

function lunchAnswers(): array
{
    return ['lunch_count' => 1, 'lunch_participants' => [['lunch_name' => 'Kim', 'lunch_diet' => ['vegan']]]];
}

function eventEndedDaysAgo(int $days): Event
{
    return openEvent(['date' => now(config('privacy.timezone'))->subDays($days)->toDateString()]);
}

it('encrypts emails at rest and finds them by keyed hash', function () {
    $e = submitEnrollment(openEvent(), ['contact_email' => 'Jane@Acme.example']);

    expect(DB::table('enrollments')->where('id', $e->id)->value('contact_email'))->not->toContain('acme')
        ->and($e->fresh()->contact_email)->toBe('jane@acme.example')
        ->and(Enrollment::whereEmail('contact_email', ' jane@acme.EXAMPLE ')->count())->toBe(1)
        ->and(EmailCrypto::hash('a@b.nl'))->toBe(EmailCrypto::hash(' A@B.nl '));
});

it('does not purge before the retention deadline', function () {
    $event = eventEndedDaysAgo(0); // the event is today: not over yet
    $e = submitEnrollment($event);
    $e->forceFill(['status' => 'approved'])->save();

    expect(cleanup()['enrollments_purged_after_event'])->toBe(0)
        ->and($e->fresh()->contact_email)->not->toBeNull();
});

it('purges contact data after the event but keeps the enrollment, status and company link', function () {
    $event = eventEndedDaysAgo(60);
    $e = submitEnrollment($event);
    $e->forceFill(['status' => 'approved'])->save();

    expect(cleanup()['enrollments_purged_after_event'])->toBe(1);

    $e->refresh();
    expect($e->contact_email)->toBeNull()->and($e->answers)->toBeNull()->and($e->status)->toBe('approved')
        ->and($e->event_id)->toBe($event->id)->and($e->personal_data_purged_at)->not->toBeNull();
});

it('keeps pending applications however old', function () {
    $e = submitEnrollment(eventEndedDaysAgo(400));

    $stats = cleanup();

    expect($stats['enrollments_purged_after_event'])->toBe(0)->and($stats['pending_left_alone'])->toBe(1)
        ->and($e->fresh()->contact_email)->not->toBeNull();
});

it('purges rejected forms three calendar months after handling, not before', function () {
    $e = submitEnrollment(openEvent());
    $e->forceFill(['status' => 'rejected', 'reviewed_at' => now()->subMonthsNoOverflow(3)->addDay()])->save();
    expect(cleanup()['enrollments_purged_rejected'])->toBe(0);

    $e->forceFill(['reviewed_at' => now()->subMonthsNoOverflow(3)->subMinute()])->save();
    expect(cleanup()['enrollments_purged_rejected'])->toBe(1)->and($e->fresh()->contact_email)->toBeNull();
});

it('removes dietary answers seven days after the event by stable key and keeps the rest', function () {
    config(['privacy.retention.enrollment_contact_days_after_event' => 365]); // isolate the dietary rule
    $event = eventEndedDaysAgo(8);
    [$e, $token] = approvedEnrollment($event);
    $sub = submitInfo($token, $event, lunchAnswers());
    expect($sub->fresh()->answers['lunch_participants'])->toHaveCount(1);

    expect(cleanup()['dietary_submissions_cleaned'])->toBe(1);

    $sub->refresh();
    expect($sub->answers['lunch_participants'])->toBe([])->and(json_encode($sub->answers))->not->toContain('vegan')
        ->and($sub->answers)->toHaveKey('drinks_attending')->and($sub->sensitive_purged_at)->not->toBeNull();
});

it('leaves dietary answers in place until seven days have passed', function () {
    config(['privacy.retention.enrollment_contact_days_after_event' => 365]); // isolate the dietary rule
    $event = eventEndedDaysAgo(3);
    [$e, $token] = approvedEnrollment($event);
    $sub = submitInfo($token, $event, lunchAnswers());

    expect(cleanup()['dietary_submissions_cleaned'])->toBe(0)->and($sub->fresh()->answers['lunch_participants'])->toHaveCount(1);
});

it('honours the sensitive flag in a schema and drops whole repeater rows', function () {
    $schema = ['sections' => [['fields' => [['key' => 'x_allergy', 'sensitive' => true]]]]];
    $out = SensitiveAnswers::strip($schema, ['x_allergy' => 'peanut', 'keep' => 1, 'people' => [['name' => 'A', 'lunch_diet' => 'halal']]]);

    expect($out)->toBe(['keep' => 1, 'people' => []]);
});

it('is idempotent and supports dry-run without changes', function () {
    $e = submitEnrollment(eventEndedDaysAgo(60));
    $e->forceFill(['status' => 'approved'])->save();

    expect(cleanup(true)['enrollments_purged_after_event'])->toBe(1)->and($e->fresh()->contact_email)->not->toBeNull();
    cleanup();
    expect(cleanup()['enrollments_purged_after_event'])->toBe(0);
});

it('purges borrel guests after the event but keeps the row for attendance counts', function () {
    $event = eventEndedDaysAgo(40);
    $g = BorrelEnrollment::create(['event_id' => $event->id, 'name' => 'Pat', 'email' => 'pat@x.example']);

    expect(cleanup()['borrel_guests_purged'])->toBe(1);
    $g->refresh();
    expect($g->email)->toBeNull()->and($g->name)->toBeNull()->and(BorrelEnrollment::where('event_id', $event->id)->count())->toBe(1);
});

it('allows the same email at several events and on several records', function () {
    $a = openEvent(['name' => 'A']);
    $b = openEvent(['name' => 'B', 'date' => '2028-03-01']);
    BorrelEnrollment::create(['event_id' => $a->id, 'name' => 'P', 'email' => 'p@x.example']);
    BorrelEnrollment::create(['event_id' => $b->id, 'name' => 'P', 'email' => 'p@x.example']);

    expect(BorrelEnrollment::whereEmail('email', 'P@x.example')->count())->toBe(2);
    expect(fn () => BorrelEnrollment::create(['event_id' => $a->id, 'name' => 'P', 'email' => 'P@X.example']))->toThrow(Illuminate\Database\QueryException::class);
});

it('purges handled access requests after three months and leaves pending ones', function () {
    $old = App\Models\CompanyAccessRequest::create(['type' => 'new_company', 'status' => 'rejected', 'company_name' => 'X', 'contact_name' => 'Pat', 'contact_email' => 'p@x.example', 'message' => 'hi', 'submitted_at' => now()->subYear(), 'reviewed_at' => now()->subMonthsNoOverflow(3)->subDay()]);
    $recent = App\Models\CompanyAccessRequest::create(['type' => 'new_company', 'status' => 'rejected', 'company_name' => 'Y', 'contact_name' => 'Q', 'contact_email' => 'q@x.example', 'submitted_at' => now(), 'reviewed_at' => now()->subMonth()]);
    $pending = App\Models\CompanyAccessRequest::create(['type' => 'new_company', 'status' => 'pending', 'company_name' => 'Z', 'contact_name' => 'R', 'contact_email' => 'r@x.example', 'submitted_at' => now()->subYear()]);

    expect(cleanup(true)['access_requests_purged'])->toBe(1)->and($old->fresh()->contact_email)->not->toBeNull();
    cleanup();

    expect($old->fresh()->contact_email)->toBeNull()->and($old->fresh()->contact_name)->toBeNull()
        ->and($recent->fresh()->contact_email)->toBe('q@x.example')->and($pending->fresh()->contact_email)->toBe('r@x.example');
});

it('defaults enrollment and borrel contact deletion to the day after the event', function () {
    expect(config('privacy.retention.enrollment_contact_days_after_event'))->toBe(0)->and(config('privacy.retention.borrel_days_after_event'))->toBe(0);
});
