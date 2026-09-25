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

test('company access page lists companies without exposing verification links', function () {
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

    Mail::assertSent(CompanyAccessApprovedMail::class, function (CompanyAccessApprovedMail $mail): bool {
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

    Mail::assertSent(CompanyAccessApprovedMail::class, function (CompanyAccessApprovedMail $mail): bool {
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

test('access request stays pending when the approval email fails', function () {
    Mail::shouldReceive('to')
        ->once()
        ->with('jane@acme.example')
        ->andReturnSelf();

    Mail::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('SMTP unavailable'));

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

    expect($request->approve())->toBeFalse()
        ->and($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_PENDING)
        ->and($company->refresh()->profile_contact_email)->toBeNull();
});
