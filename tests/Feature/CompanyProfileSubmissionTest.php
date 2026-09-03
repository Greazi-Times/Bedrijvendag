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

test('company can open its verification form with a token', function () {
    $education = Education::create(['name' => 'Informatica']);
    $sector = Sector::create(['name' => 'Software']);

    $company = Company::create([
        'name' => 'Acme',
        'website_url' => 'https://acme.example',
        'description' => ['html' => '<p>Current description</p>'],
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
    $company = Company::create([
        'name' => 'Formatted Company',
        'description' => ['html' => '<p>Old</p>'],
    ]);

    $this->post(route('company-profile.update', $company->profile_token), [
        'name' => 'Formatted Company',
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

    expect($company->refresh()->description['html'])
        ->toContain('<strong>Bold</strong>')
        ->toContain('<u>underline</u>');
});

test('company submission is stored for review and does not immediately update public data', function () {
    Storage::fake('public');

    $education = Education::create(['name' => 'Mechatronica']);
    $sector = Sector::create(['name' => 'Engineering']);

    $company = Company::create([
        'name' => 'Old Name',
        'website_url' => 'https://old.example',
        'description' => ['html' => '<p>Old description</p>'],
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

test('approving a submission updates the company profile', function () {
    Mail::fake();

    $education = Education::create(['name' => 'Elektrotechniek']);
    $sector = Sector::create(['name' => 'Energy']);

    $company = Company::create([
        'name' => 'Before',
        'website_url' => 'https://before.example',
        'description' => ['html' => '<p>Before description</p>'],
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
        ->and($company->description['html'])->toBe('<p>After description</p>')
        ->and($company->educations()->pluck('education.id')->all())->toBe([$education->id])
        ->and($company->sectors()->pluck('sectors.id')->all())->toBe([$sector->id]);

    expect($submission->fresh()->status)->toBe(CompanyProfileSubmission::STATUS_APPROVED);

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

    Mail::assertSent(CompanyProfileRejectedMail::class, function (CompanyProfileRejectedMail $mail): bool {
        return $mail->hasTo('fallback@example.com')
            && $mail->submission->review_note === 'Please shorten the description.';
    });
});
