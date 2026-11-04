<?php

use App\Mail\CompanyAccessApprovedMail;
use App\Models\Company;
use App\Models\CompanyAccessRequest;
use App\Models\Education;
use App\Models\Sector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('company access page lists companies without exposing edit links', function () {
    $company = Company::create([
        'name' => 'Acme',
        'website_url' => 'https://acme.example',
    ]);
    $education = Education::create(['name' => 'Mechatronica']);
    $sector = Sector::create(['name' => 'Techniek']);

    $this->get(route('company-access.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CompanyAccess/RequestCompanyAccess')
            ->where('companies.0.id', $company->id)
            ->where('companies.0.name', 'Acme')
            ->where('options.educations.0.id', $education->id)
            ->where('options.sectors.0.id', $sector->id)
            ->missing('companies.0.profile_token')
            ->missing('companies.0.profile_verification_url')
        );
});

test('existing company access request does not reveal or edit the company', function () {
    $company = Company::create([
        'name' => 'Acme',
        'profile_contact_email' => null,
    ]);

    $this->post(route('company-access.store'), [
        'type' => CompanyAccessRequest::TYPE_EXISTING,
        'company_id' => $company->id,
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@acme.example',
        'message' => 'Please send access.',
    ])->assertRedirect();

    $company->refresh();

    expect($company->profile_contact_email)->toBeNull();

    $request = CompanyAccessRequest::query()->firstOrFail();

    expect($request->status)->toBe(CompanyAccessRequest::STATUS_PENDING)
        ->and($request->type)->toBe(CompanyAccessRequest::TYPE_EXISTING)
        ->and($request->company_id)->toBe($company->id)
        ->and($request->company_name)->toBe('Acme')
        ->and($request->contact_email)->toBe('jane@acme.example');
});

test('approving an existing company access request can set the profile contact email', function () {
    Mail::fake();

    $company = Company::create([
        'name' => 'Acme',
        'profile_contact_email' => null,
    ]);

    $request = CompanyAccessRequest::create([
        'type' => CompanyAccessRequest::TYPE_EXISTING,
        'status' => CompanyAccessRequest::STATUS_PENDING,
        'company_id' => $company->id,
        'company_name' => $company->name,
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@acme.example',
        'submitted_at' => now(),
    ]);

    $request->approve();

    expect($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_APPROVED)
        ->and($company->refresh()->profile_contact_email)->toBe('jane@acme.example')
        ->and($request->fresh()->verificationUrl())->toBe($company->profileVerificationUrl());

    Mail::assertQueued(CompanyAccessApprovedMail::class, function (CompanyAccessApprovedMail $mail): bool {
        return $mail->hasTo('jane@acme.example')
            && $mail->accessRequest->verificationUrl() !== null;
    });
});

test('new company request only creates a company after approval', function () {
    Mail::fake();
    Storage::fake('public');

    $education = Education::create(['name' => 'Mechatronica']);
    $sector = Sector::create(['name' => 'Techniek']);

    $this->post(route('company-access.store'), [
        'type' => CompanyAccessRequest::TYPE_NEW,
        'company_name' => 'New Company',
        'website_url' => 'https://new.example',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'description' => "Slimme oplossingen voor de industrie.\n\nWij werken aan robotica.",
        'education_ids' => [$education->id],
        'sector_ids' => [$sector->id],
        'new_sector_names' => ['Robotica'],
        'contact_name' => 'John Doe',
        'contact_email' => 'john@new.example',
    ])->assertRedirect();

    $this->assertDatabaseMissing('companies', [
        'name' => 'New Company',
    ]);

    $request = CompanyAccessRequest::query()->firstOrFail();

    expect($request->description)->toContain('Slimme oplossingen')
        ->and($request->education_ids)->toBe([$education->id])
        ->and($request->sector_ids)->toBe([$sector->id])
        ->and($request->new_sector_names)->toBe(['Robotica'])
        ->and($request->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($request->logo_path);

    $request->approve();

    $company = Company::query()->where('name', 'New Company')->firstOrFail();

    expect($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_APPROVED)
        ->and($request->fresh()->company_id)->toBe($company->id)
        ->and($company->website_url)->toBe('https://new.example')
        ->and($company->logo_path)->toBe($request->logo_path)
        ->and($company->description_nl)->toContain('<p>Slimme oplossingen voor de industrie.</p>')
        ->and($company->educations()->pluck('education.id')->all())->toBe([$education->id])
        ->and($company->sectors()->pluck('sectors.name')->all())->toContain('Techniek', 'Robotica')
        ->and($company->profile_contact_email)->toBe('john@new.example')
        ->and($company->profile_token)->not->toBeNull();

    Mail::assertQueued(CompanyAccessApprovedMail::class, function (CompanyAccessApprovedMail $mail): bool {
        return $mail->hasTo('john@new.example')
            && $mail->accessRequest->verificationUrl() !== null;
    });
});

test('new company request requires the public profile information', function () {
    $this->post(route('company-access.store'), [
        'type' => CompanyAccessRequest::TYPE_NEW,
        'company_name' => 'Incomplete Company',
        'contact_name' => 'John Doe',
        'contact_email' => 'john@new.example',
    ])->assertSessionHasErrors(['logo', 'description', 'education_ids']);

    $this->assertDatabaseMissing('company_access_requests', [
        'company_name' => 'Incomplete Company',
    ]);
});

function pendingNewRequest(string $name = 'BOM Engineering'): CompanyAccessRequest
{
    return CompanyAccessRequest::create([
        'type' => CompanyAccessRequest::TYPE_NEW,
        'status' => CompanyAccessRequest::STATUS_PENDING,
        'company_name' => $name,
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@bom.example',
        'submitted_at' => now(),
    ]);
}

test('company name matching ignores case, whitespace and punctuation and suggests similar names', function () {
    $company = Company::create(['name' => 'BOM Engineering B.V.']);

    expect(Company::matchesForName('  bom   engineering bv ')['exact']->pluck('id')->all())->toBe([$company->id])
        ->and(Company::matchesForName('BOM Enginering BV')['exact'])->toBeEmpty()
        ->and(Company::matchesForName('BOM Enginering BV')['similar']->pluck('id')->all())->toBe([$company->id])
        ->and(Company::matchesForName('Totally Different')['similar'])->toBeEmpty();

    $this->getJson(route('company-access.match', ['name' => 'bom engineering bv']))
        ->assertOk()
        ->assertJsonPath('exact.0.id', $company->id)
        ->assertJsonMissingPath('exact.0.profile_token');
});

test('new company request with an existing name is rejected by the server', function () {
    Storage::fake('public');
    Company::create(['name' => 'BOM Engineering']);

    $this->post(route('company-access.store'), [
        'type' => CompanyAccessRequest::TYPE_NEW,
        'company_name' => ' bom engineering ',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'description' => 'Beschrijving',
        'education_ids' => [Education::create(['name' => 'Mechatronica'])->id],
        'contact_name' => 'Jane',
        'contact_email' => 'jane@bom.example',
    ])->assertSessionHasErrors('company_name');

    expect(CompanyAccessRequest::count())->toBe(0);
});

test('approving a new request whose company appeared meanwhile is blocked', function () {
    Mail::fake();
    $request = pendingNewRequest();
    Company::create(['name' => 'BOM Engineering']);

    expect(fn () => $request->approve())->toThrow(Illuminate\Validation\ValidationException::class);
    expect(Company::count())->toBe(1)
        ->and($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_PENDING);
    Mail::assertNothingQueued();
});

test('approve and link existing company keeps company data and sends the custom message once', function () {
    Mail::fake();
    $sector = Sector::create(['name' => 'Techniek']);
    $company = Company::create(['name' => 'BOM Engineering', 'website_url' => 'https://bom.example', 'profile_contact_email' => null]);
    $company->sectors()->sync([$sector->id]);
    $request = pendingNewRequest('BOM Enginering');

    expect($request->approve(null, "Welkom <b>terug</b>\nGroet", $company->id))->toBeTrue()
        ->and($request->approve(null, 'again', $company->id))->toBeFalse();

    $request->refresh();
    $company->refresh();

    expect($request->status)->toBe(CompanyAccessRequest::STATUS_APPROVED)
        ->and($request->company_id)->toBe($company->id)
        ->and(Company::count())->toBe(1)
        ->and($company->website_url)->toBe('https://bom.example')
        ->and($company->sectors()->pluck('sectors.id')->all())->toBe([$sector->id])
        ->and($company->profile_contact_email)->toBe('jane@bom.example');

    Mail::assertQueued(CompanyAccessApprovedMail::class, 1);
    Mail::assertQueued(CompanyAccessApprovedMail::class, fn ($mail) => str_contains($mail->render(), 'Welkom &lt;b&gt;terug&lt;/b&gt;<br />')
        && str_contains($mail->render(), $company->profileVerificationUrl()));
});

test('approve and link action is limited to reviewers', function () {
    Mail::fake();
    $request = pendingNewRequest('BOM Enginering');

    $this->seed(Database\Seeders\RolesAndPermissionsSeeder::class);
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('dashboard'));
    $viewer = App\Models\User::factory()->create();
    $viewer->assignRole(Spatie\Permission\Models\Role::findOrCreate('Viewer')->syncPermissions(['dashboard.view', 'company-access-requests.view']));
    $this->actingAs($viewer);
    Livewire\Livewire::test(App\Filament\Resources\CompanyAccessRequests\Pages\ViewCompanyAccessRequest::class, ['record' => $request->getRouteKey()])
        ->assertActionHidden('approveAndLink');

    expect($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_PENDING);
    Mail::assertNothingQueued();
});

function reviewerOn(string $page, CompanyAccessRequest $request)
{
    test()->seed(Database\Seeders\RolesAndPermissionsSeeder::class);
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('dashboard'));
    test()->withoutMiddleware(App\Http\Middleware\MustTwoFactor::class);
    test()->actingAs(tap(App\Models\User::factory()->create())->assignRole(App\Support\Permissions::ADMINISTRATOR));

    return Livewire\Livewire::test($page, ['record' => $request->getRouteKey()]);
}

test('rejecting because the company exists prefills the message, queues the email and leaves companies alone', function () {
    Mail::fake();
    $company = Company::create(['name' => 'BOM Engineering']);
    $before = $company->fresh()->only('name', 'profile_contact_email', 'profile_token');
    $request = pendingNewRequest('BOM Enginering');
    $page = App\Filament\Resources\CompanyAccessRequests\Pages\ViewCompanyAccessRequest::class;

    reviewerOn($page, $request)
        ->mountAction('reject')
        ->setActionData(['reason' => 'company_exists'])
        ->assertActionDataSet(['message' => App\Filament\Resources\CompanyAccessRequests\ReviewActions::COMPANY_EXISTS_MESSAGE])
        ->setActionData(['reason' => 'company_exists', 'message' => 'Eigen <i>tekst</i>'])
        ->callMountedAction()
        ->assertNotified('Access request rejected successfully.');

    expect($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_REJECTED)
        ->and($request->fresh()->review_note)->toBe('Company already exists')
        ->and(Company::count())->toBe(1)
        ->and($company->fresh()->only('name', 'profile_contact_email', 'profile_token'))->toBe($before);

    Mail::assertQueued(App\Mail\CompanyAccessRejectedMail::class, 1);
    Mail::assertQueued(App\Mail\CompanyAccessRejectedMail::class, function ($mail) {
        $html = $mail->render();

        return $mail->hasTo('jane@bom.example')
            && str_contains($html, 'BOM Enginering')
            && str_contains($html, 'Eigen &lt;i&gt;tekst&lt;/i&gt;')
            && str_contains($html, route('company-access.create'))
            && ! str_contains($html, '/bedrijf-profiel/');
    });
});

test('general rejection without a message sends no email and a repeated rejection sends nothing', function () {
    Mail::fake();
    $request = pendingNewRequest();

    expect($request->reject('internal'))->toBeTrue()
        ->and($request->notificationQueued)->toBeNull()
        ->and($request->reject('again', 'Hallo'))->toBeFalse()
        ->and(CompanyAccessRequest::find($request->id)->reject('again', 'Hallo'))->toBeFalse();

    Mail::assertNothingQueued();
});

test('rejection with a message is emailed once even when repeated', function () {
    Mail::fake();
    $request = pendingNewRequest();

    expect($request->reject(null, 'Hallo'))->toBeTrue()
        ->and($request->notificationQueued)->toBeTrue()
        ->and(CompanyAccessRequest::find($request->id)->reject(null, 'Hallo'))->toBeFalse();

    Mail::assertQueued(App\Mail\CompanyAccessRejectedMail::class, 1);
});

test('emails are queued only after the transaction commits and never after a rollback', function () {
    Mail::fake();
    $request = pendingNewRequest();

    try {
        DB::transaction(function () use ($request) {
            $request->reject(null, 'Hallo');
            Mail::assertNothingQueued();
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }

    Mail::assertNothingQueued();

    $request = pendingNewRequest('Other Co');
    DB::transaction(function () use ($request) {
        $request->reject(null, 'Hallo');
        Mail::assertNothingQueued();
    });
    Mail::assertQueued(App\Mail\CompanyAccessRejectedMail::class, 1);
});

test('a mail dispatch failure does not undo a committed approval and is reported', function () {
    Mail::shouldReceive('to')->once()->andReturnSelf();
    Mail::shouldReceive('queue')->once()->andThrow(new RuntimeException('queue down'));
    $company = Company::create(['name' => 'Acme']);
    $request = pendingNewRequest('Acme Corp');

    expect($request->approve(null, null, $company->id))->toBeTrue()
        ->and($request->notificationQueued)->toBeFalse()
        ->and($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_APPROVED);
});

test('approval queues its email after commit with feedback', function () {
    Mail::fake();
    $company = Company::create(['name' => 'Acme']);
    $request = pendingNewRequest('Acme Corp');

    expect($request->approve(null, null, $company->id))->toBeTrue()
        ->and($request->notificationQueued)->toBeTrue();
    Mail::assertQueued(CompanyAccessApprovedMail::class, 1);
});
