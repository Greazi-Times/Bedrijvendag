<?php

use App\Mail\CompanyAccessApprovedMail;
use App\Models\Company;
use App\Models\CompanyAccessRequest;
use App\Models\Event;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('company interest page does not expose company or verification data', function () {
    $this->get(route('company-access.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CompanyAccess/Create')
            ->missing('companies')
        );
});

test('company access page shows the next event to prospective companies', function () {
    Event::create([
        'name' => 'ATIx Bedrijvendag 2027',
        'date' => today()->addMonth(),
    ]);

    $this->get(route('company-access.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcomingEvent.name', 'ATIx Bedrijvendag 2027')
            ->where('upcomingEvent.date', today()->addMonth()->toDateString())
        );
});

test('existing company access request does not reveal or edit the company', function () {
    $company = Company::create([
        'name' => 'Acme',
        'profile_contact_email' => null,
    ]);

    $this->post(route('company-access.store'), [
        'company_name' => 'ACME',
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

    $this->post(route('company-access.store'), [
        'company_name' => 'New Company',
        'website_url' => 'https://new.example',
        'contact_name' => 'John Doe',
        'contact_email' => 'john@new.example',
    ])->assertRedirect();

    $this->assertDatabaseMissing('companies', [
        'name' => 'New Company',
    ]);

    $request = CompanyAccessRequest::query()->firstOrFail();

    $request->approve();

    $company = Company::query()->where('name', 'New Company')->firstOrFail();

    expect($request->fresh()->status)->toBe(CompanyAccessRequest::STATUS_APPROVED)
        ->and($request->fresh()->company_id)->toBe($company->id)
        ->and($company->website_url)->toBe('https://new.example')
        ->and($company->profile_contact_email)->toBe('john@new.example')
        ->and($company->profile_token)->not->toBeNull();

    Mail::assertSent(CompanyAccessApprovedMail::class, function (CompanyAccessApprovedMail $mail): bool {
        return $mail->hasTo('john@new.example')
            && $mail->accessRequest->verificationUrl() !== null;
    });
});

test('company name is required for an interest request', function () {
    $this->post(route('company-access.store'), [
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@acme.example',
        'message' => 'We would like to attend.',
    ])->assertSessionHasErrors('company_name');
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
