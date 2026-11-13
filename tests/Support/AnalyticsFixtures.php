<?php

use App\Models\Company;
use App\Models\Education;
use App\Models\Enrollment;
use App\Models\EnrollmentInfoSubmission;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\Partner;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
| Fixtures shared by the analytics tests (see the table in AnalyticsTest.php).
*/

function analyticsEdition(string $name, string $date, array $extra = []): Event
{
    return openEvent($extra + ['name' => $name, 'date' => $date, 'max_stands' => 5, 'partner_stand_count' => 2]);
}

function analyticsEnroll(Event $event, Company $company, string $status = 'approved', array $extra = []): Enrollment
{
    static $n = 0;
    $n++;
    $submitted = $extra['submitted_at'] ?? Carbon::parse($event->date)->subDays(60);

    return Enrollment::query()->forceCreate(array_diff_key($extra, ['submitted_at' => 1]) + [
        'event_id' => $event->id, 'form_version_id' => $event->enrollmentForm->latestVersion->id,
        'selected_company_id' => $company->id, 'company_id' => $status === 'approved' ? $company->id : null, 'approved_company_id' => $status === 'approved' ? $company->id : null,
        'status' => $status, 'info_status' => $status === 'approved' ? 'awaiting' : 'not_invited',
        'company_name' => $company->name, 'name_key' => Company::nameKey($company->name), 'contact_name' => 'Contact', 'contact_email' => "contact{$n}@example.test",
        'answers' => [], 'fee_cents_accepted' => 25000, 'currency_accepted' => 'EUR', 'terms_version_accepted' => 1, 'accepted_at' => $submitted, 'submission_key' => (string) Illuminate\Support\Str::uuid(),
        'submitted_at' => $submitted, 'reviewed_at' => $status === 'pending' ? null : Carbon::parse($submitted)->addDays(2),
    ]);
}

function analyticsInfo(Enrollment $enrollment, int $revision, array $answers): EnrollmentInfoSubmission
{
    $enrollment->forceFill(['info_status' => 'submitted'])->save();

    return EnrollmentInfoSubmission::query()->create([
        'enrollment_id' => $enrollment->id, 'form_version_id' => $enrollment->event->infoForm->latestVersion->id, 'revision' => $revision,
        'answers' => $answers, 'submitted_at' => Carbon::parse('2026-11-01')->addDays($revision),
    ]);
}

/** @return array<string, mixed> */
function analyticsWorld(): array
{
    $e = [
        1 => analyticsEdition('Editie 2023', '2023-03-01'), 2 => analyticsEdition('Editie 2024', '2024-03-01'), 3 => analyticsEdition('Editie 2025', '2025-03-01'),
        4 => analyticsEdition('Editie 2026', '2026-03-01'), 5 => analyticsEdition('Editie 2027', '2027-03-01'),
    ];
    $c = collect(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'])->mapWithKeys(fn (string $n): array => [$n => Company::create(['name' => "Bedrijf {$n}"])])->all();
    $stand = fn (int $edition, string $company, int $number = 1) => legacyStand($e[$edition], $c[$company], $number);

    // Historical editions: stands only.
    $stand(1, 'A');
    $stand(1, 'B', 2);
    $stand(1, 'C', 3);
    $stand(2, 'A');
    $stand(2, 'F', 2);

    // Enrollment era.
    foreach ([3 => ['A', 'C'], 4 => ['A', 'E'], 5 => ['A', 'C', 'D', 'E', 'G']] as $edition => $names) {
        foreach ($names as $name) {
            analyticsEnroll($e[$edition], $c[$name], extra: $edition === 5 && $name === 'D' ? ['fee_cents_accepted' => 20000] : []);
        }
    }

    $stand(3, 'A');
    $stand(3, 'C', 2);
    $stand(4, 'A');
    $stand(4, 'F', 2); // grandfathered: stand without an enrollment
    analyticsEnroll($e[5], $c['H'], 'pending', ['fee_cents_accepted' => 10000]);
    analyticsEnroll($e[5], $c['I'], 'rejected', ['fee_cents_accepted' => 30000]);

    // Stands of the upcoming edition: A has two, C one, B has one without an enrollment (flagged).
    $stand(5, 'A', 1);
    $stand(5, 'A', 2);
    $stand(5, 'C', 3);
    $stand(5, 'B', 4);
    $partner = Partner::create(['name' => 'Partner', 'description' => 'x', 'logo' => 'partners/x.png']);
    EventStand::create(['event_id' => $e[5]->id, 'type' => 'partner', 'stand_number' => '1', 'partner_id' => $partner->id]);

    // Second forms of the upcoming edition. G sends three revisions; only the last one counts.
    $enrollment = fn (string $n) => Enrollment::query()->where('event_id', $e[5]->id)->where('company_id', $c[$n]->id)->firstOrFail();
    analyticsInfo($enrollment('A'), 1, ['lunch_count' => 2, 'lunch_participants' => [['lunch_diet' => ['vegetarian']], ['lunch_diet' => ['gluten_free', 'lactose_free']]], 'drinks_attending' => 'yes', 'drinks_count' => 4, 'promo_materials' => ['medium_banners']]);
    analyticsInfo($enrollment('C'), 1, ['lunch_count' => 0, 'drinks_attending' => 'unsure', 'promo_materials' => ['small_desk_materials', 'medium_banners']]);
    $g = $enrollment('G');
    analyticsInfo($g, 1, ['lunch_count' => 1, 'lunch_participants' => [['lunch_diet' => ['halal']]], 'drinks_attending' => 'no', 'promo_materials' => ['small_brochures']]);
    analyticsInfo($g, 2, ['lunch_count' => 2, 'lunch_participants' => [['lunch_diet' => ['halal']], ['lunch_diet' => ['halal']]], 'drinks_attending' => 'no', 'promo_materials' => ['small_brochures']]);
    analyticsInfo($g, 3, ['lunch_count' => 3, 'lunch_participants' => [['lunch_diet' => ['vegetarian', 'vegan']], ['lunch_diet' => ['halal']], ['lunch_diet' => ['none']]], 'drinks_attending' => 'yes', 'drinks_count' => 2, 'promo_materials' => ['large_screens', 'small_brochures']]);

    // Sectors and educations (current profile).
    [$s1, $s2] = [Sector::create(['name' => 'IT']), Sector::create(['name' => 'Bouw'])];
    [$d1, $d2] = [Education::create(['name' => 'Informatica']), Education::create(['name' => 'Techniek'])];
    $c['A']->sectors()->attach([$s1->id, $s2->id]);
    $c['C']->sectors()->attach($s1->id);
    $c['D']->sectors()->attach($s2->id);
    $c['G']->sectors()->attach($s1->id);
    $c['A']->educations()->attach([$d1->id, $d2->id]);
    $c['C']->educations()->attach($d1->id);

    return compact('e', 'c') + ['sectors' => [$s1, $s2]];
}

function analyticsUser(array $permissions): User
{
    Permission::query()->exists() || test()->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    test()->withoutMiddleware(MustTwoFactor::class);
    $role = Role::create(['name' => 'Stats'.uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::whereIn('name', $permissions)->get());

    return User::factory()->create()->assignRole($role);
}

function analyticsAdmin(): User
{
    Permission::query()->exists() || test()->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    test()->withoutMiddleware(MustTwoFactor::class);

    return User::factory()->create()->assignRole(App\Support\Permissions::SUPER_ADMIN);
}

function analyticsIds(array $companies, array $names): array
{
    return collect($names)->map(fn (string $n): int => $companies[$n]->id)->sort()->values()->all();
}

function analyticsSorted(array $values): array
{
    sort($values);

    return $values;
}
