<?php

use App\Models\CompanyInterestRequest;
use App\Models\Event;
use Inertia\Testing\AssertableInertia as Assert;

test('company interest page shows the next edition', function () {
    $event = Event::withoutEvents(fn () => Event::create([
        'name' => 'ATIx Bedrijvendag 2027',
        'date' => today()->addMonth(),
    ]));

    $this->get(route('company-interest.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CompanyInterest/Create')
            ->where('upcomingEvent.name', 'ATIx Bedrijvendag 2027')
            ->where('upcomingEvent.date', $event->date->toDateString())
        );
});

test('company can ask about participating in the upcoming edition', function () {
    $event = Event::withoutEvents(fn () => Event::create([
        'name' => 'ATIx Bedrijvendag 2027',
        'date' => today()->addMonth(),
    ]));

    $this->post(route('company-interest.store'), [
        'company_name' => 'Acme',
        'website_url' => 'https://acme.example',
        'contact_name' => 'Jane Doe',
        'contact_email' => 'jane@acme.example',
        'message' => 'We would like to attend. Are stands still available?',
    ])->assertRedirect();

    $request = CompanyInterestRequest::query()->firstOrFail();

    expect($request->event_id)->toBe($event->id)
        ->and($request->company_name)->toBe('Acme')
        ->and($request->contact_email)->toBe('jane@acme.example')
        ->and($request->message)->toBe('We would like to attend. Are stands still available?');
});

test('company interest form validates required contact details', function () {
    $this->post(route('company-interest.store'))
        ->assertSessionHasErrors(['company_name', 'contact_name', 'contact_email']);
});
