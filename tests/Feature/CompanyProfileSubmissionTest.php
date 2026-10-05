<?php

use App\Mail\CompanyProfileApprovedMail;
use App\Mail\CompanyProfileRejectedMail;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Sector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('review page shows changed and unchanged fields with their current and proposed values', function () {
    $company = Company::create(['name' => 'Original company', 'logo_path' => 'company-logos/original.png']);
    $submission = CompanyProfileSubmission::create([
        'company_id' => $company->id,
        'status' => CompanyProfileSubmission::STATUS_PENDING,
        'proposed_name' => 'Proposed company',
        'proposed_description' => '<p>New description</p>',
    ]);

    $comparison = $submission->profileComparison();
    expect($comparison['logo']['changed'])->toBeFalse()
        ->and($comparison['logo']['after'])->toBe('company-logos/original.png')
        ->and($comparison['name']['changed'])->toBeTrue();

    $this->actingAs(\App\Models\User::factory()->create());
    \Livewire\Livewire::test(\App\Filament\Resources\CompanyProfileSubmissions\Pages\ViewCompanyProfileSubmission::class, [
        'record' => $submission->id,
    ])->assertSuccessful()
        ->assertSee('Original company')
        ->assertSeeHtml('class="profile-diff-ins" title="Added">Proposed</ins> company')
        ->assertSee('Changed')
        ->assertSee('Unchanged')
        ->assertSee('New description');
});

test('older reviewed submissions do not pretend the current profile is the original', function () {
    $company = Company::create(['name' => 'Already changed']);
    $submission = CompanyProfileSubmission::create([
        'company_id' => $company->id,
        'status' => CompanyProfileSubmission::STATUS_APPROVED,
        'proposed_name' => 'Already changed',
    ]);

    expect($submission->profileComparison())->toBe([]);
});

test('company can open its verification form with a token', function () {
    $education = Education::create(['name' => 'Informatica']);
    $sector = Sector::create(['name' => 'Software']);

    $company = Company::create([
        'name' => 'Acme',
        'website_url' => 'https://acme.example',
        'description_nl' => '<p>Current description</p>',
        'description_en' => '<p>English description</p>',
    ]);

    $company->educations()->attach($education);
    $company->sectors()->attach($sector);

    $this->get(route('company-profile.edit', $company->profile_token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CompanyProfile/Edit')
            ->where('company.name', 'Acme')
            ->where('company.website_url', 'https://acme.example')
            ->where('company.description', '<p>Current description</p>')
            ->where('company.education_ids.0', $education->id)
            ->where('company.sector_ids.0', $sector->id)
        );
});

test('company rich text submission keeps safe formatting and removes unsafe markup', function () {
    Mail::fake();

    $company = Company::create([
        'name' => 'Formatted Company',
        'description_nl' => '<p>Old</p>',
    ]);

    $this->post(route('company-profile.update', $company->profile_token), [
        'name' => 'Formatted Company',
        'contact_name' => 'Jane Doe',
        'contact_email' => 'profile@example.com',
        'description' => '<h2>Summary</h2><p><strong>Bold</strong> and <em>italic</em> with <u>underline</u>.</p><ul><li>One</li></ul><a href="javascript:alert(1)" onclick="alert(1)">Bad link</a><script>alert(1)</script>',
        'education_ids' => [],
        'sector_ids' => [],
    ])->assertRedirect();

    $submission = CompanyProfileSubmission::query()->firstOrFail();

    expect($submission->proposed_description)
        ->toContain('<h2>Summary</h2>')
        ->toContain('<strong>Bold</strong>')
        ->toContain('<em>italic</em>')
        ->toContain('<u>underline</u>')
        ->toContain('<ul><li>One</li></ul>')
        ->not->toContain('javascript:')
        ->not->toContain('onclick')
        ->not->toContain('<script>');

    $submission->approve();

    expect($company->refresh()->description_nl)
        ->toContain('<strong>Bold</strong>')
        ->toContain('<u>underline</u>');
});

test('company description length is measured on visible text instead of html markup', function () {
    $company = Company::create([
        'name' => 'Pasted Description Company',
    ]);

    $paragraph = '<p style="margin:0cm;font-family:Calibri,sans-serif;font-size:11pt;line-height:115%"><span style="font-size:11pt;color:#000000">'.str_repeat('a', 170).'</span></p>';

    $this->post(route('company-profile.update', $company->profile_token), [
        'name' => 'Pasted Description Company',
        'contact_name' => 'Jane Doe',
        'contact_email' => 'profile@example.com',
        'description' => str_repeat($paragraph, 10),
        'education_ids' => [],
        'sector_ids' => [],
    ])->assertSessionHasNoErrors();

    expect(CompanyProfileSubmission::query()->count())->toBe(1);
});

test('company description longer than 5000 visible characters is rejected', function () {
    $company = Company::create([
        'name' => 'Long Description Company',
    ]);

    $this->post(route('company-profile.update', $company->profile_token), [
        'name' => 'Long Description Company',
        'contact_name' => 'Jane Doe',
        'contact_email' => 'profile@example.com',
        'description' => '<p>'.str_repeat('a', 5001).'</p>',
        'education_ids' => [],
        'sector_ids' => [],
    ])->assertSessionHasErrors('description');

    expect(CompanyProfileSubmission::query()->count())->toBe(0);
});

test('company profile submission requires contact details', function () {
    $company = Company::create([
        'name' => 'Required Contact Company',
    ]);

    $this->from(route('company-profile.edit', $company->profile_token))
        ->post(route('company-profile.update', $company->profile_token), [
            'name' => 'Required Contact Company',
            'description' => 'Description',
            'education_ids' => [],
            'sector_ids' => [],
        ])
        ->assertRedirect(route('company-profile.edit', $company->profile_token))
        ->assertSessionHasErrors(['contact_name', 'contact_email']);

    expect(CompanyProfileSubmission::query()->count())->toBe(0);
});

test('company submission is stored for review and does not immediately update public data', function () {
    Storage::fake('public');

    $education = Education::create(['name' => 'Mechatronica']);
    $sector = Sector::create(['name' => 'Engineering']);

    $company = Company::create([
        'name' => 'Old Name',
        'website_url' => 'https://old.example',
        'description_nl' => '<p>Old description</p>',
    ]);

    $this->post(route('company-profile.update', $company->profile_token), [
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@example.com',
        'name' => 'New Name',
        'website_url' => 'https://new.example',
        'description' => '<script>alert(1)</script>New description',
        'education_ids' => [$education->id],
        'sector_ids' => [$sector->id],
        'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertRedirect();

    $company->refresh();

    expect($company->name)->toBe('Old Name');

    $submission = CompanyProfileSubmission::query()->firstOrFail();

    expect($submission->status)->toBe(CompanyProfileSubmission::STATUS_PENDING)
        ->and($submission->proposed_name)->toBe('New Name')
        ->and($submission->proposed_description)->toBe('New description')
        ->and($submission->proposed_education_ids)->toBe([$education->id])
        ->and($submission->proposed_sector_ids)->toBe([$sector->id]);

    Storage::disk('public')->assertExists($submission->proposed_logo_path);
});

test('company can propose a new sector that is only created after approval', function () {
    Mail::fake();

    $company = Company::create([
        'name' => 'Sector Company',
        'description_nl' => '<p>Current description</p>',
    ]);

    $this->post(route('company-profile.update', $company->profile_token), [
        'contact_name' => 'Jane Doe',
        'contact_email' => 'profile@example.com',
        'name' => 'Sector Company',
        'description' => 'Current description',
        'education_ids' => [],
        'sector_ids' => [],
        'new_sector_names' => ['Biotechnologie', ' biotechnologie ', 'Software & AI'],
    ])->assertRedirect();

    $this->assertDatabaseMissing('sectors', [
        'name' => 'Biotechnologie',
    ]);

    $submission = CompanyProfileSubmission::query()->firstOrFail();

    expect($submission->proposed_new_sector_names)->toBe(['Biotechnologie', 'Software & AI'])
        ->and($submission->proposedSectorNames())->toBe('Biotechnologie (new), Software & AI (new)');

    expect($submission->approve())->toBeTrue();

    $this->assertDatabaseHas('sectors', [
        'name' => 'Biotechnologie',
    ]);

    $this->assertDatabaseHas('sectors', [
        'name' => 'Software & AI',
    ]);

    expect($company->refresh()->sectors()->pluck('name')->sort()->values()->all())
        ->toBe(['Biotechnologie', 'Software & AI']);
});

test('approving a submission updates the company profile', function () {
    Mail::fake();

    $education = Education::create(['name' => 'Elektrotechniek']);
    $sector = Sector::create(['name' => 'Energy']);

    $company = Company::create([
        'name' => 'Before',
        'website_url' => 'https://before.example',
        'description_nl' => '<p>Before description</p>',
    ]);

    $submission = CompanyProfileSubmission::create([
        'company_id' => $company->id,
        'status' => CompanyProfileSubmission::STATUS_PENDING,
        'proposed_name' => 'After',
        'contact_email' => 'profile@example.com',
        'proposed_logo_path' => 'company-logos/logo.png',
        'proposed_website_url' => 'https://after.example',
        'proposed_description' => 'After description',
        'proposed_education_ids' => [$education->id],
        'proposed_sector_ids' => [$sector->id],
        'submitted_at' => now(),
    ]);

    $submission->approve('Looks good');

    $company->refresh();

    expect($company->name)->toBe('After')
        ->and($company->website_url)->toBe('https://after.example')
        ->and($company->logo_path)->toBe('company-logos/logo.png')
        ->and($company->description_nl)->toBe('<p>After description</p>')
        ->and($company->educations()->pluck('education.id')->all())->toBe([$education->id])
        ->and($company->sectors()->pluck('sectors.id')->all())->toBe([$sector->id]);

    expect($submission->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_APPROVED);

    $comparison = $submission->fresh()->profileComparison();
    expect($comparison['name'])->toBe(['before' => 'Before', 'after' => 'After', 'changed' => true])
        ->and($comparison['description']['before'])->toBe('<p>Before description</p>')
        ->and($comparison['educations']['after'])->toBe('Elektrotechniek');

    $company->update(['name' => 'Later change']);
    expect($submission->fresh()->profileComparison())->toBe($comparison);

    Mail::assertSent(CompanyProfileApprovedMail::class, fn (CompanyProfileApprovedMail $mail): bool => $mail->hasTo('profile@example.com'));
});

test('rejecting a submission emails the company contact', function () {
    Mail::fake();

    $company = Company::create([
        'name' => 'Rejected Company',
        'profile_contact_email' => 'fallback@example.com',
    ]);

    $submission = CompanyProfileSubmission::create([
        'company_id' => $company->id,
        'status' => CompanyProfileSubmission::STATUS_PENDING,
        'proposed_name' => 'Rejected Company',
        'submitted_at' => now(),
    ]);

    $submission->reject('Please shorten the description.');

    expect($submission->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_REJECTED);
    expect($submission->fresh()->profileComparison()['name']['changed'])->toBeFalse();

    Mail::assertSent(CompanyProfileRejectedMail::class, function (CompanyProfileRejectedMail $mail): bool {
        return $mail->hasTo('fallback@example.com')
            && $mail->submission->review_note === 'Please shorten the description.';
    });
});

test('profile submission stays pending and does not update the company when the approval email fails', function () {
    Mail::shouldReceive('to')
        ->once()
        ->with('profile@example.com')
        ->andReturnSelf();

    Mail::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('SMTP unavailable'));

    $company = Company::create([
        'name' => 'Before',
        'website_url' => 'https://before.example',
        'description_nl' => '<p>Before description</p>',
    ]);

    $submission = CompanyProfileSubmission::create([
        'company_id' => $company->id,
        'status' => CompanyProfileSubmission::STATUS_PENDING,
        'contact_email' => 'profile@example.com',
        'proposed_name' => 'After',
        'proposed_website_url' => 'https://after.example',
        'proposed_description' => 'After description',
        'submitted_at' => now(),
    ]);

    expect($submission->approve())->toBeFalse()
        ->and($submission->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_PENDING)
        ->and($company->refresh()->name)->toBe('Before')
        ->and($company->website_url)->toBe('https://before.example')
        ->and($company->description_nl)->toBe('<p>Before description</p>');
});
