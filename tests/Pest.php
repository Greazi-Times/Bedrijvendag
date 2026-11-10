<?php

use App\Mail\EnrollmentApprovedMail;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\User;
use App\Services\EnrollmentWorkflow;
use App\Services\InformationWorkflow;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function enrollmentAnswers(array $over = []): array
{
    return $over + [
        'company_name' => 'Acme BV', 'company_description' => 'We build things.',
        'contact_name' => 'Jane', 'contact_email' => 'jane@acme.example',
        'invoice_street' => 'Straat 1', 'invoice_postal_code' => '1234AB', 'invoice_city' => 'Breda', 'invoice_country' => 'Nederland', 'invoice_contact_name' => 'Fin', 'invoice_contact_email' => 'fin@acme.example',
        'terms_accept' => true,
    ];
}

function openEvent(array $attrs = []): Event
{
    $event = Event::create($attrs + [
        'name' => 'Bedrijvendag 2027', 'date' => '2027-03-01', 'description' => ['type' => 'doc', 'content' => []],
        'enrollment_enabled' => true, 'fee_cents' => 25000, 'currency' => 'EUR', 'terms_html' => '<p>Terms v1</p>',
    ]);

    // New editions inherit the global default forms; nothing is provisioned per edition.
    return $event->refresh();
}

function enrollmentPayload(Event $event, array $answers = [], array $extra = []): array
{
    return $extra + [
        'submission_key' => (string) Illuminate\Support\Str::uuid(),
        'form_version_id' => $event->enrollmentForm->latestVersion->id,
        'answers' => enrollmentAnswers($answers),
    ];
}

function submitEnrollment(Event $event, array $answers = [], array $extra = []): Enrollment
{
    return app(EnrollmentWorkflow::class)->submit($event, enrollmentPayload($event, $answers, $extra));
}

function reviewer(array $extra = []): User
{
    // Seeded once per test: the helpers below are called many times and the seeder is expensive.
    Permission::query()->exists() || test()->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    test()->withoutMiddleware(MustTwoFactor::class);
    $role = Role::create(['name' => 'Reviewer'.uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::whereIn('name', array_merge(['enrollments.view', 'enrollments.review'], $extra))->get());

    return User::factory()->create()->assignRole($role);
}

function reviewerWithout(array $permissions = []): User
{
    // Seeded once per test: the helpers below are called many times and the seeder is expensive.
    Permission::query()->exists() || test()->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    test()->withoutMiddleware(MustTwoFactor::class);
    $role = Role::create(['name' => 'Limited'.uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::whereIn('name', $permissions)->get());

    return User::factory()->create()->assignRole($role);
}

/** An approved enrollment for a new company plus the plain invitation token from the approval email. */
function approvedEnrollment(?Event $event = null, array $answers = [], ?Company $company = null): array
{
    Mail::fake();
    $event ??= openEvent();
    $e = submitEnrollment($event, $answers, $company ? ['selected_company_id' => $company->id] : []);
    $mode = $company ? ['mode' => 'existing', 'company_id' => $company->id] : ['mode' => 'new'];
    app(EnrollmentWorkflow::class)->approve($e, reviewer(), $mode);

    $token = null;
    Mail::assertQueued(EnrollmentApprovedMail::class, function ($m) use (&$token) {
        $token = $m->token;

        return true;
    });

    return [$e->fresh(), $token, $event];
}

function infoAnswers(array $over = []): array
{
    return $over + ['contact_name' => 'Jane', 'contact_email' => 'jane@acme.example', 'drinks_attending' => 'no'];
}

function infoPayload(Event $event, array $answers = [], array $verification = [], array $extra = []): array
{
    return $extra + [
        'form_version_id' => $event->infoForm->latestVersion->id,
        'answers' => infoAnswers($answers),
        // The global information form makes the profile check mandatory, so a submission always carries a choice.
        'verification' => $verification ?: ['choice' => 'confirmed'],
    ];
}

function submitInfo(string $token, Event $event, array $answers = [], array $verification = []): App\Models\EnrollmentInfoSubmission
{
    return app(InformationWorkflow::class)->submit($token, infoPayload($event, $answers, $verification));
}

function standEvent(array $attrs = []): Event
{
    return openEvent($attrs + ['max_stands' => 5, 'partner_stand_count' => 2]);
}

function historicalEvent(): Event
{
    return openEvent(['name' => 'Bedrijvendag 2020', 'date' => '2020-03-01', 'max_stands' => 5, 'partner_stand_count' => 1]);
}

/** A company with a real approval for the edition, obtained through the normal workflow. */
function approvedFor(Event $event, string $name = 'Acme BV'): Company
{
    Mail::fake();
    $e = submitEnrollment($event, ['company_name' => $name, 'contact_email' => strtolower(str_replace(' ', '', $name)).'@x.example']);
    app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'new']);

    return $e->fresh()->company;
}

/** A company whose enrollment for this edition has the given status (no company is created for pending or rejected). */
function enrolledAs(Event $event, string $status, string $name): Company
{
    Mail::fake();
    $company = Company::create(['name' => $name]);
    $e = submitEnrollment($event, ['company_name' => $name, 'contact_email' => strtolower(str_replace(' ', '', $name)).'@x.example'], ['selected_company_id' => $company->id]);

    if ($status === 'rejected') {
        app(EnrollmentWorkflow::class)->reject($e, reviewer(), 'no');
    }

    return $company;
}

function emptyStand(Event $event, int $number = 1): EventStand
{
    return EventStand::create(['event_id' => $event->id, 'type' => 'company', 'stand_number' => (string) $number]);
}

/** An assignment that predates the rules: written without model events, exactly like old data. */
function legacyStand(Event $event, Company $company, int $number = 1): EventStand
{
    return EventStand::withoutEvents(fn () => EventStand::create(['event_id' => $event->id, 'type' => 'company', 'stand_number' => (string) $number, 'company_id' => $company->id, 'x_percent' => 5, 'y_percent' => 5]));
}

/** The company Select of the mounted Assign action, as the admin would see it. */
function companySelect($component)
{
    $page = $component->instance();

    return collect($page->getSchema($page->getMountedActionSchemaName())->getFlatComponents())->first(fn ($c) => $c->getName() === 'company_id');
}

function assignFails(EventStand $stand, Company $company): bool
{
    try {
        $stand->update(['company_id' => $company->id]);

        return false;
    } catch (ValidationException) {
        return true;
    }
}
