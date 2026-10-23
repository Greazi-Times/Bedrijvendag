<?php

use App\Exceptions\OutdatedSubmission;
use App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource;
use App\Filament\Resources\CompanyProfileSubmissions\Pages\ListCompanyProfileSubmissions;
use App\Filament\Resources\CompanyProfileSubmissions\Pages\ViewCompanyProfileSubmission;
use App\Mail\CompanyProfileApprovedMail;
use App\Mail\CompanyProfileRejectedMail;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * @param  array<int, string>  $abilities
 */
function submissionReviewer(array $abilities = ['view', 'review']): User
{
    $permissions = collect($abilities)
        ->map(fn (string $ability) => Permission::findOrCreate("company-profile-submissions.{$ability}"))
        ->push(Permission::findOrCreate('dashboard.view'));

    return User::factory()->create()->givePermissionTo($permissions);
}

/**
 * Sends the company form like the edit page does.
 *
 * @param  array<string, mixed>  $changes
 */
function sendProfile(Company $company, array $changes = []): Illuminate\Testing\TestResponse
{
    return test()->post(route('company-profile.update', $company->profile_token), [
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@example.com',
        'name' => $company->name,
        'website_url' => $company->website_url,
        'description' => $company->description_nl,
        'education_ids' => [],
        'sector_ids' => [],
        ...$changes,
    ]);
}

beforeEach(fn () => Mail::fake());

test('a first submission opens a review with one pending version', function () {
    $company = Company::create(['name' => 'First Co']);

    sendProfile($company, ['name' => 'First Company'])->assertSessionHas('company_profile_result', 'created');

    $submission = CompanyProfileSubmission::query()->sole();
    expect($submission->status)->toBe(CompanyProfileSubmission::STATUS_PENDING)
        ->and($submission->revision)->toBe(1)
        ->and($submission->submitted_at)->not->toBeNull()
        ->and($company->refresh()->name)->toBe('First Co');
});

test('an identical resend creates no version and keeps the timestamp', function () {
    $company = Company::create(['name' => 'Same Co']);
    sendProfile($company, ['name' => 'Same Company']);
    $submittedAt = CompanyProfileSubmission::query()->sole()->submitted_at;

    $this->travel(5)->minutes();
    sendProfile($company, ['name' => 'Same Company'])->assertSessionHas('company_profile_result', 'duplicate');

    expect(CompanyProfileSubmission::query()->sole()->submitted_at->equalTo($submittedAt))->toBeTrue();
    Mail::assertNothingSent();
});

test('changes after an approval start a new review and leave the approved profile alone until approved', function () {
    $company = Company::create(['name' => 'Approved Co', 'profile_contact_email' => 'contact@example.com']);
    sendProfile($company, ['name' => 'Approved Company']);
    CompanyProfileSubmission::query()->sole()->approve();

    expect($company->refresh()->name)->toBe('Approved Company');

    sendProfile($company, ['name' => 'Approved Company Next'])->assertSessionHas('company_profile_result', 'created');

    $latest = CompanyProfileSubmission::query()->latest('id')->first();
    expect($latest->status)->toBe(CompanyProfileSubmission::STATUS_PENDING)
        ->and($latest->revision)->toBe(1)
        ->and($company->refresh()->name)->toBe('Approved Company')
        ->and(CompanyProfileSubmission::query()->where('status', CompanyProfileSubmission::STATUS_APPROVED)->count())->toBe(1);
});

test('corrected information after a rejection is a new review and the rejection stays in history', function () {
    $company = Company::create(['name' => 'Rejected Co', 'profile_contact_email' => 'contact@example.com']);
    sendProfile($company, ['name' => 'Wrong Name']);
    $rejected = CompanyProfileSubmission::query()->sole();
    $rejected->reject('Typo');

    sendProfile($company, ['name' => 'Right Name'])->assertSessionHas('company_profile_result', 'created');

    $pending = $company->pendingProfileSubmission()->first();
    expect($pending->proposed_name)->toBe('Right Name')
        ->and($rejected->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_REJECTED)
        ->and($pending->history()->pluck('id')->all())->toBe([$pending->id, $rejected->id]);
});

test('the database allows only one pending submission per company but any amount of history', function () {
    $company = Company::create(['name' => 'Constraint Co']);
    $attributes = ['company_id' => $company->id, 'proposed_name' => 'X', 'submitted_at' => now()];

    foreach ([CompanyProfileSubmission::STATUS_SUPERSEDED, CompanyProfileSubmission::STATUS_SUPERSEDED, CompanyProfileSubmission::STATUS_APPROVED, CompanyProfileSubmission::STATUS_REJECTED, CompanyProfileSubmission::STATUS_PENDING] as $status) {
        CompanyProfileSubmission::create([...$attributes, 'status' => $status]);
    }

    // What a second, simultaneous request would do if it got past the lock.
    expect(fn () => CompanyProfileSubmission::create([...$attributes, 'status' => CompanyProfileSubmission::STATUS_PENDING]))
        ->toThrow(UniqueConstraintViolationException::class);

    $other = Company::create(['name' => 'Other Co']);
    CompanyProfileSubmission::create([...$attributes, 'company_id' => $other->id, 'status' => CompanyProfileSubmission::STATUS_PENDING]);

    expect(CompanyProfileSubmission::query()->where('status', CompanyProfileSubmission::STATUS_PENDING)->count())->toBe(2);
});

test('the review queue lists each company once and history stays reachable through the filter', function () {
    $asml = Company::create(['name' => 'ASML']);
    $philips = Company::create(['name' => 'Philips']);

    sendProfile($asml, ['name' => 'ASML 1']);
    sendProfile($asml, ['name' => 'ASML 2']);
    sendProfile($asml, ['name' => 'ASML 3']);
    sendProfile($philips, ['name' => 'Philips 1']);

    $asmlPending = $asml->pendingProfileSubmission()->first();
    $philipsPending = $philips->pendingProfileSubmission()->first();
    $superseded = CompanyProfileSubmission::query()->where('status', CompanyProfileSubmission::STATUS_SUPERSEDED)->get();

    expect($asmlPending->revision)->toBe(3)->and($superseded)->toHaveCount(2);

    $this->actingAs(submissionReviewer(['view']));

    Livewire::test(ListCompanyProfileSubmissions::class)
        ->assertCanSeeTableRecords([$asmlPending, $philipsPending])
        ->assertCanNotSeeTableRecords($superseded)
        ->assertCountTableRecords(2)
        ->searchTable('Philips')
        ->assertCanSeeTableRecords([$philipsPending])
        ->assertCanNotSeeTableRecords([$asmlPending]);

    Livewire::test(ListCompanyProfileSubmissions::class)
        ->filterTable('status', CompanyProfileSubmission::STATUS_SUPERSEDED)
        ->assertCanSeeTableRecords($superseded)
        ->assertCanNotSeeTableRecords([$asmlPending, $philipsPending]);

    expect(CompanyProfileSubmissionResource::getNavigationBadge())->toBe('2');
});

test('the submission page shows the latest version and its stored history', function () {
    $company = Company::create(['name' => 'History Co', 'website_url' => 'https://old.example']);
    sendProfile($company, ['name' => 'History Company']);
    sendProfile($company, ['name' => 'History Company', 'website_url' => 'https://new.example']);

    $pending = $company->pendingProfileSubmission()->first();
    $first = CompanyProfileSubmission::query()->where('status', CompanyProfileSubmission::STATUS_SUPERSEDED)->sole();

    $this->actingAs(submissionReviewer());

    Livewire::test(ViewCompanyProfileSubmission::class, ['record' => $pending->id])
        ->assertSee('Submission history')
        ->assertSee('Company submitted changes')
        ->assertSee('Company updated its pending changes (update 2)')
        ->assertSee('Changed: Website')
        ->assertSee('Superseded')
        ->assertActionVisible('approve');

    // An earlier version is read-only and points to the latest one.
    Livewire::test(ViewCompanyProfileSubmission::class, ['record' => $first->id])
        ->assertSee('Earlier version')
        ->assertSee('Review the latest version')
        ->assertActionHidden('approve')
        ->assertActionHidden('reject');
});

test('approving applies the latest version and closes the review', function () {
    $company = Company::create(['name' => 'Approve Co']);
    sendProfile($company, ['name' => 'Approve One']);
    sendProfile($company, ['name' => 'Approve Two']);
    $pending = $company->pendingProfileSubmission()->first();

    $this->actingAs(submissionReviewer());

    Livewire::test(ViewCompanyProfileSubmission::class, ['record' => $pending->id])
        ->callAction('approve', ['review_note' => 'Fine'])
        ->assertNotified('Company profile updated');

    expect($company->refresh()->name)->toBe('Approve Two')
        ->and($pending->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_APPROVED)
        ->and($company->pendingProfileSubmission()->exists())->toBeFalse()
        ->and(CompanyProfileSubmission::query()->where('status', CompanyProfileSubmission::STATUS_SUPERSEDED)->count())->toBe(1);

    Mail::assertSent(CompanyProfileApprovedMail::class, 1);
});

test('rejecting closes the review and keeps the approved profile', function () {
    $company = Company::create(['name' => 'Keep Co']);
    sendProfile($company, ['name' => 'Not This']);
    $pending = $company->pendingProfileSubmission()->first();

    $this->actingAs(submissionReviewer());

    Livewire::test(ViewCompanyProfileSubmission::class, ['record' => $pending->id])
        ->callAction('reject', ['review_note' => 'No'])
        ->assertNotified('Submission rejected');

    expect($company->refresh()->name)->toBe('Keep Co')
        ->and($pending->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_REJECTED);

    Mail::assertSent(CompanyProfileRejectedMail::class, 1);
});

test('an approval of a version that was updated after the page was opened is refused', function () {
    $company = Company::create(['name' => 'Race Co']);
    sendProfile($company, ['name' => 'Opened Version']);
    $opened = $company->pendingProfileSubmission()->first();

    $this->actingAs(submissionReviewer());
    $page = Livewire::test(ViewCompanyProfileSubmission::class, ['record' => $opened->id]);

    // The company sends more changes while the administrator is looking at the page.
    sendProfile($company, ['name' => 'Newer Version']);
    $latest = $company->pendingProfileSubmission()->first();

    $page->callAction('approve')
        ->assertNotified('Submission changed')
        ->assertSee('Earlier version')
        ->assertActionHidden('approve');

    expect($company->refresh()->name)->toBe('Race Co')
        ->and($opened->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_SUPERSEDED)
        ->and($latest->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_PENDING);

    Mail::assertNothingSent();
});

test('a rejection of an outdated version is refused and the model reports why', function () {
    $company = Company::create(['name' => 'Stale Co', 'profile_contact_email' => 'contact@example.com']);
    sendProfile($company, ['name' => 'Stale One']);
    $opened = $company->pendingProfileSubmission()->first();
    sendProfile($company, ['name' => 'Stale Two']);

    expect(fn () => $opened->reject('Old'))->toThrow(
        OutdatedSubmission::class,
        'This submission has been updated since you opened it. Please review the latest changes before continuing.',
    );

    // Reviewed by someone else in the meantime.
    $latest = $company->pendingProfileSubmission()->first();
    $stale = CompanyProfileSubmission::find($latest->id);
    $latest->approve();

    expect(fn () => $stale->approve())->toThrow(OutdatedSubmission::class, 'This submission has already been reviewed.');
    Mail::assertSent(CompanyProfileApprovedMail::class, 1);
    Mail::assertNotSent(CompanyProfileRejectedMail::class);
});

test('reviewing needs the review permission and viewing needs the view permission', function () {
    $company = Company::create(['name' => 'Perm Co']);
    sendProfile($company, ['name' => 'Perm Company']);
    $pending = $company->pendingProfileSubmission()->first();

    $this->actingAs(submissionReviewer(['view']));
    Livewire::test(ViewCompanyProfileSubmission::class, ['record' => $pending->id])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject');

    $this->actingAs(User::factory()->create());
    $this->get(CompanyProfileSubmissionResource::getUrl('view', ['record' => $pending]))->assertNotFound();

    expect($pending->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_PENDING);
});

test('a company edit link only reaches its own company', function () {
    $own = Company::create(['name' => 'Own Co']);
    $other = Company::create(['name' => 'Other Co']);

    sendProfile($own, ['name' => 'Own Company']);
    $this->post(route('company-profile.update', 'not-a-token'), ['name' => 'X'])->assertNotFound();

    expect($own->pendingProfileSubmission()->exists())->toBeTrue()
        ->and($other->profileSubmissions()->exists())->toBeFalse();
});

test('the migration keeps the latest of several pending submissions pending and flags lost changes', function () {
    $migration = require database_path('migrations/2026_10_15_000000_add_revisions_to_company_profile_submissions.php');
    $migration->down();

    $company = Company::create(['name' => 'Legacy Co', 'logo_path' => 'company-logos/published.png', 'website_url' => 'https://published.example']);
    $clean = Company::create(['name' => 'Clean Co']);
    $row = fn (Company $for, array $values, string $at) => DB::table('company_profile_submissions')->insertGetId([
        'company_id' => $for->id,
        'status' => 'pending',
        'proposed_name' => $for->name,
        'proposed_logo_path' => $for->logo_path,
        'proposed_website_url' => $for->website_url,
        'proposed_education_ids' => '[]',
        'proposed_sector_ids' => '[]',
        'submitted_at' => $at,
        'created_at' => $at,
        'updated_at' => $at,
        ...$values,
    ]);

    $first = $row($company, ['proposed_logo_path' => 'company-logos/new-logo.png'], '2026-10-10 10:00:00');
    $second = $row($company, ['proposed_name' => 'Legacy Company'], '2026-10-10 11:00:00');
    $third = $row($company, ['proposed_name' => 'Legacy Company', 'proposed_website_url' => 'https://new.example'], '2026-10-10 12:00:00');
    $approved = $row($company, ['status' => 'approved'], '2026-10-01 09:00:00');
    $single = $row($clean, ['proposed_name' => 'Clean Company'], '2026-10-10 10:00:00');

    $migration->up();
    $migration->up();

    $statuses = DB::table('company_profile_submissions')->orderBy('id')->pluck('status', 'id');
    $revisions = DB::table('company_profile_submissions')->orderBy('id')->pluck('revision', 'id');

    expect($statuses->all())->toBe([
        $first => 'superseded',
        $second => 'superseded',
        $third => 'pending',
        $approved => 'approved',
        $single => 'pending',
    ])->and([$revisions[$first], $revisions[$second], $revisions[$third], $revisions[$single]])->toBe([1, 2, 3, 1])
        ->and(DB::table('company_profile_submissions')->count())->toBe(5);

    // The first submission proposed a logo the latest one dropped; the second one is contained in the third.
    $note = DB::table('company_profile_submissions')->where('id', $third)->value('attention_note');
    expect($note)->toContain("Submission #{$first}: Logo")
        ->not->toContain("Submission #{$second}")
        ->and(DB::table('company_profile_submissions')->where('id', $single)->value('attention_note'))->toBeNull();
});
