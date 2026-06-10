<?php

use App\Models\BorrelEnrollment;
use App\Models\Event;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow('2026-06-10 09:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('home shows no planned borrel state when there is no upcoming event', function () {
    Event::create([
        'name' => 'Past edition',
        'date' => '2026-06-01',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('borrelStatus', 'none')
            ->where('borrelEvent', null)
            ->where('borrelEnrollmentOpen', false)
            ->where('closingBorrelCount', 0)
        );
});

test('home shows happy connecting state on the event date', function () {
    $event = Event::create([
        'name' => 'Today edition',
        'date' => '2026-06-10',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('borrelStatus', 'today')
            ->where('borrelEvent.id', $event->id)
            ->where('borrelEnrollmentOpen', false)
            ->where('closingBorrelCount', 0)
        );
});

test('home exposes borrel enrollment for a future event', function () {
    $event = Event::create([
        'name' => 'Future edition',
        'date' => '2026-06-11',
    ]);

    BorrelEnrollment::create([
        'event_id' => $event->id,
        'name' => 'Existing Student',
        'email' => 'existing@example.com',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('borrelStatus', 'open')
            ->where('borrelEvent.id', $event->id)
            ->where('borrelEnrollmentOpen', true)
            ->where('closingBorrelCount', 1)
        );
});

test('borrel signup only accepts future events', function () {
    $todayEvent = Event::create([
        'name' => 'Today edition',
        'date' => '2026-06-10',
    ]);

    $this->post('/borrel-signup', [
        'event_id' => $todayEvent->id,
        'name' => 'Today Student',
        'email' => 'today@example.com',
    ])->assertSessionHasErrors('event_id');

    $this->assertDatabaseMissing('borrel_enrollments', [
        'email' => 'today@example.com',
    ]);

    $futureEvent = Event::create([
        'name' => 'Future edition',
        'date' => '2026-06-11',
    ]);

    $this->post('/borrel-signup', [
        'event_id' => $futureEvent->id,
        'name' => 'Future Student',
        'email' => 'future@example.com',
    ])->assertRedirect();

    $this->assertDatabaseHas('borrel_enrollments', [
        'event_id' => $futureEvent->id,
        'name' => 'Future Student',
        'email' => 'future@example.com',
    ]);
});
