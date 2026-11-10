<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Filament\Resources\EventStands\Pages\ManageStands;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\EventStand;
use App\Models\Partner;
use App\Services\StandEligibility;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('an approved company can receive a stand for its edition', function () {
    $event = standEvent();
    $company = approvedFor($event);
    $stand = emptyStand($event);

    $stand->update(['company_id' => $company->id]);

    expect($stand->fresh()->company_id)->toBe($company->id);
});

test('pending and rejected companies cannot receive a new stand', function () {
    $event = standEvent();
    $pending = enrolledAs($event, 'pending', 'Pending BV');
    $rejected = enrolledAs($event, 'rejected', 'Rejected BV');
    $stand = emptyStand($event);

    expect(assignFails($stand, $pending))->toBeTrue()->and(assignFails($stand, $rejected))->toBeTrue();
    expect($stand->fresh()->company_id)->toBeNull();
});

test('approval for another edition does not make a company eligible', function () {
    $other = standEvent(['name' => 'Other edition']);
    $event = standEvent();
    $company = approvedFor($other);
    $stand = emptyStand($event);

    expect(assignFails($stand, $company))->toBeTrue();
    emptyStand($other)->update(['company_id' => $company->id]); // still fine where it was approved
});

test('a company without any enrollment cannot receive a new stand', function () {
    $event = standEvent();
    $stand = emptyStand($event);

    expect(assignFails($stand, Company::create(['name' => 'Nobody BV'])))->toBeTrue();
    expect(fn () => EventStand::create(['event_id' => $event->id, 'type' => 'company', 'stand_number' => '2', 'company_id' => Company::first()->id]))->toThrow(ValidationException::class);
});

test('partners stay eligible without any enrollment and their stands are unchanged', function () {
    $event = standEvent();
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'partners/x.png']);
    $stand = EventStand::create(['event_id' => $event->id, 'type' => 'partner', 'stand_number' => '1']);

    $stand->update(['partner_id' => $partner->id]);

    expect($stand->fresh()->partner_id)->toBe($partner->id)->and($stand->fresh()->company_id)->toBeNull();
});

test('existing assignments are preserved and flagged, not deleted, when approval is lost', function () {
    $event = standEvent();
    $company = approvedFor($event);
    $stand = emptyStand($event);
    $stand->update(['company_id' => $company->id, 'x_percent' => 12.5, 'y_percent' => 40]);

    Enrollment::where('company_id', $company->id)->first()->forceFill(['status' => 'rejected'])->save();

    $stand->refresh();
    expect($stand->company_id)->toBe($company->id)->and($stand->x_percent)->toBe(12.5);

    $flagged = app(StandEligibility::class)->flaggedStands($event);
    expect($flagged->pluck('id')->all())->toBe([$stand->id]);
    expect(app(StandEligibility::class)->explain($stand)['label'])->toContain('rejected');
});

test('legacy assignments without an enrollment stay in place and are flagged only on active or future editions', function () {
    $future = standEvent();
    $past = historicalEvent();
    $legacyCompany = Company::create(['name' => 'Legacy BV']);
    $futureStand = legacyStand($future, $legacyCompany);
    $pastStand = legacyStand($past, $legacyCompany);

    $eligibility = app(StandEligibility::class);
    expect($eligibility->flaggedStands($future)->pluck('id')->all())->toBe([$futureStand->id])
        ->and($eligibility->flaggedStands($past))->toBeEmpty()
        ->and($eligibility->explain($futureStand)['label'])->toContain('predates enrollments')
        ->and(EventStand::count())->toBe(2);
});

test('layout and marker changes on a grandfathered stand are never blocked', function () {
    $event = standEvent();
    $stand = legacyStand($event, Company::create(['name' => 'Legacy BV']));

    $stand->update(['x_percent' => 33.3, 'y_percent' => 66.6]);
    EventStand::query()->whereKey($stand->id)->update(['x_percent' => 10, 'y_percent' => 20]); // how the map editor saves markers
    $event->stands()->update(['x_percent' => null, 'y_percent' => null]); // clearing markers in bulk

    $stand->refresh();
    expect($stand->company_id)->not->toBeNull()->and($stand->x_percent)->toBeNull();
});

test('reassigning a grandfathered stand validates the new company, and re-placing the same company is a new assignment', function () {
    $event = standEvent();
    $legacy = Company::create(['name' => 'Legacy BV']);
    $stand = legacyStand($event, $legacy, 1);
    $nobody = Company::create(['name' => 'Nobody BV']);
    $approved = approvedFor($event, 'Approved BV');

    expect(assignFails($stand, $nobody))->toBeTrue();
    expect($stand->fresh()->company_id)->toBe($legacy->id);

    // moving the legacy company to another stand number is a new placement
    $other = emptyStand($event, 2);
    expect(assignFails($other, $legacy))->toBeTrue();

    $stand->update(['company_id' => $approved->id]);
    expect($stand->fresh()->company_id)->toBe($approved->id);

    // removal then re-adding the legacy company no longer counts as grandfathered
    expect(assignFails($stand, $legacy))->toBeTrue();
});

test('moving a stand to another edition checks the destination edition', function () {
    $from = standEvent(['name' => 'From']);
    $to = standEvent(['name' => 'To']);
    $company = approvedFor($from);
    $stand = emptyStand($from);
    $stand->update(['company_id' => $company->id]);

    expect(fn () => $stand->update(['event_id' => $to->id]))->toThrow(ValidationException::class);
    expect($stand->fresh()->event_id)->toBe($from->id);

    // legacy data: moving to an edition where the company has no approval is blocked too
    $legacy = legacyStand($from, Company::create(['name' => 'Legacy BV']), 2);
    expect(fn () => $legacy->update(['event_id' => $to->id]))->toThrow(ValidationException::class);
});

test('the dropdown for a historical edition offers every company', function () {
    $past = historicalEvent();
    $stand = emptyStand($past);
    Company::create(['name' => 'Old BV']);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    $select = companySelect(Livewire::test(ManageStands::class)->set('selectedEventId', $past->id)->mountTableAction('assign', $stand));

    expect(array_values($select->getOptions()))->toBe(['Old BV']);
});

test('historical editions are not restricted', function () {
    $past = historicalEvent();
    $stand = emptyStand($past);

    $stand->update(['company_id' => Company::create(['name' => 'Old BV'])->id]);

    expect($stand->fresh()->company_id)->not->toBeNull();
});

test('bulk writes cannot bypass the eligibility check', function () {
    $event = standEvent();
    $stand = emptyStand($event);
    $nobody = Company::create(['name' => 'Nobody BV']);

    expect(fn () => EventStand::query()->whereKey($stand->id)->update(['company_id' => $nobody->id]))->toThrow(ValidationException::class);
    expect(fn () => $event->stands()->update(['company_id' => $nobody->id]))->toThrow(ValidationException::class);
    expect(fn () => EventStand::query()->insert(['event_id' => $event->id, 'type' => 'company', 'stand_number' => '9', 'company_id' => $nobody->id]))->toThrow(LogicException::class);
    expect(fn () => EventStand::query()->upsert([['event_id' => $event->id, 'type' => 'company', 'stand_number' => '9', 'company_id' => $nobody->id]], ['event_id', 'type', 'stand_number'], ['updated_at']))->toThrow(LogicException::class);
    expect(fn () => EventStand::query()->upsert([['event_id' => $event->id, 'type' => 'company', 'stand_number' => '1']], ['event_id', 'type', 'stand_number'], ['company_id']))->toThrow(LogicException::class);
    expect($stand->fresh()->company_id)->toBeNull();

    // a bulk move of a legacy stand into another edition is checked per stand
    $other = standEvent(['name' => 'Other']);
    $legacy = legacyStand($event, Company::create(['name' => 'Legacy BV']), 3);
    expect(fn () => EventStand::query()->whereKey($legacy->id)->update(['event_id' => $other->id]))->toThrow(ValidationException::class);
    expect($legacy->fresh()->event_id)->toBe($event->id);

    // unassigning and creating empty rows in bulk stay possible
    EventStand::query()->whereKey($legacy->id)->update(['company_id' => null]);
    EventStand::query()->upsert([['event_id' => $event->id, 'type' => 'company', 'stand_number' => '4', 'company_id' => null, 'partner_id' => null, 'created_at' => now(), 'updated_at' => now()]], ['event_id', 'type', 'stand_number'], ['updated_at']);
    expect($legacy->fresh()->company_id)->toBeNull()->and(EventStand::where('stand_number', '4')->exists())->toBeTrue();

    // approved companies can still be moved in bulk
    $approved = approvedFor($event, 'Approved BV');
    EventStand::query()->whereKey($stand->id)->update(['company_id' => $approved->id]);
    expect($stand->fresh()->company_id)->toBe($approved->id);
});

test('no code path writes event_stands outside the guarded model, builder or checked actions', function () {
    $allowed = [
        'app/Models/Event.php',                                   // clears markers when the map image changes
        'app/Models/Builders/EventStandBuilder.php',
    ];
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $relative = 'app/'.str_replace([app_path().'/', DIRECTORY_SEPARATOR], ['', '/'], $file->getPathname());
        $code = file_get_contents($file->getPathname());

        if (preg_match("/table\\(['\"]event_stands['\"]\\)/", $code) && ! in_array($relative, $allowed, true)) {
            $offenders[] = "{$relative}: raw event_stands table write";
        }
        if (preg_match('/companies\(\)\s*->\s*(attach|sync|syncWithoutDetaching|toggle)\(/', $code) && ! str_contains($code, 'StandEligibility')) {
            $offenders[] = "{$relative}: pivot write without StandEligibility";
        }
    }

    expect($offenders)->toBe([]);
});

test('the Event Map dropdown lists only companies approved for the stands own edition', function () {
    $event = standEvent();
    $other = standEvent(['name' => 'Other edition']);
    $approved = approvedFor($event, 'Approved BV');
    $pending = enrolledAs($event, 'pending', 'Pending BV');
    $rejected = enrolledAs($event, 'rejected', 'Rejected BV');
    $elsewhere = approvedFor($other, 'Elsewhere BV');
    $legacy = Company::create(['name' => 'Legacy BV']);
    $stand = emptyStand($event);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    $component = Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->mountTableAction('assign', $stand);
    $select = companySelect($component);

    expect(array_values($select->getOptions()))->toBe(['Approved BV']);
    expect(array_values($select->getSearchResults('BV')))->toBe(['Approved BV']);
    expect($select->getSearchResults('Pending'))->toBe([])->and($select->getSearchResults('Elsewhere'))->toBe([])->and($select->getSearchResults('Legacy'))->toBe([]);
});

test('the dropdown keeps showing an existing ineligible company without offering it to others', function () {
    $event = standEvent();
    $legacy = Company::create(['name' => 'Legacy BV']);
    $stand = legacyStand($event, $legacy);
    $free = emptyStand($event, 2);
    approvedFor($event, 'Approved BV');
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    $options = fn (EventStand $s) => array_values(companySelect(Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->mountTableAction('assign', $s))->getOptions());

    expect($options($stand))->toContain('Legacy BV')->toContain('Approved BV');
    expect($options($free))->not->toContain('Legacy BV');
});

test('the assign action rejects a hand-crafted company and ignores a tampered edition property', function () {
    $event = standEvent();
    $other = standEvent(['name' => 'Other edition']);
    $notApproved = enrolledAs($event, 'pending', 'Pending BV');
    $elsewhere = approvedFor($other, 'Elsewhere BV');
    $stand = emptyStand($event);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)
        ->callTableAction('assign', $stand, ['company_id' => $notApproved->id])->assertHasTableActionErrors(['company_id']);

    // the page property says "Other edition", but the stand belongs to the first one: the action cannot be run on it
    expect(fn () => Livewire::test(ManageStands::class)->set('selectedEventId', $other->id)
        ->callTableAction('assign', $stand, ['company_id' => $elsewhere->id]))->toThrow(Filament\Actions\Exceptions\ActionNotResolvableException::class);

    expect($stand->fresh()->company_id)->toBeNull();
});

test('the assign action assigns an approved company and moving it empties the previous stand', function () {
    $event = standEvent();
    $company = approvedFor($event);
    $first = emptyStand($event, 1);
    $second = emptyStand($event, 2);
    $first->update(['company_id' => $company->id]);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->callTableAction('assign', $second, ['company_id' => $company->id])->assertHasNoTableActionErrors();

    expect($second->fresh()->company_id)->toBe($company->id)->and($first->fresh()->company_id)->toBeNull();
});

test('partner stand allocation through the Event Map is unchanged', function () {
    $event = standEvent();
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'partners/x.png']);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));
    $page = Livewire::test(ManageStands::class)->set('selectedEventId', $event->id);
    $stand = EventStand::where('event_id', $event->id)->where('type', 'partner')->orderBy('stand_number')->first();

    $page->callTableAction('assign', $stand, ['partner_id' => $partner->id])->assertHasNoTableActionErrors();

    expect($stand->fresh()->partner_id)->toBe($partner->id)->and($stand->fresh()->type)->toBe('partner');
});

test('the Event Map warns about flagged stands without changing them', function () {
    $event = standEvent();
    $legacy = Company::create(['name' => 'Legacy BV']);
    $stand = legacyStand($event, $legacy);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)
        ->assertSee('without an approved enrollment')->assertSee('Legacy BV')->assertSee('predates enrollments');

    expect($stand->fresh()->company_id)->toBe($legacy->id);
});

test('syncing stand rows and removing assignments keep stand data', function () {
    $event = standEvent();
    $stand = legacyStand($event, Company::create(['name' => 'Legacy BV']), 1);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->callTableAction('remove', $stand);

    $stand->refresh();
    expect($stand->company_id)->toBeNull()->and($stand->x_percent)->toBe(5.0)->and((string) $stand->stand_number)->toBe('1');
});

test('invitations record that the email was queued, not that it was delivered', function () {
    [$e] = approvedEnrollment();

    expect(Illuminate\Support\Facades\Schema::hasColumn('enrollment_invitations', 'queued_at'))->toBeTrue()
        ->and(Illuminate\Support\Facades\Schema::hasColumn('enrollment_invitations', 'sent_at'))->toBeFalse();

    $this->actingAs(reviewer());
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSee('Email queued')->assertSee('delivery is not tracked');
});
