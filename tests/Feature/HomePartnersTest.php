<?php

use App\Models\Event;
use App\Models\Partner;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow('2026-06-19 09:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('home displays the organising partners for the highlighted event', function () {
    $event = Event::create([
        'name' => 'Upcoming edition',
        'date' => '2026-06-20',
    ]);

    $zebra = Partner::create([
        'name' => 'Zebra Partner',
        'url' => 'https://zebra.example',
        'image' => 'partners/zebra.png',
    ]);

    $alpha = Partner::create([
        'name' => 'Alpha Partner',
        'url' => 'https://alpha.example',
        'image' => 'partners/alpha.png',
    ]);

    $event->eventPartners()->attach([$zebra->id, $alpha->id]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('partners', 2)
            ->where('partners.0.id', $alpha->id)
            ->where('partners.0.name', 'Alpha Partner')
            ->where('partners.0.image_url', '/storage/partners/alpha.png')
            ->where('partners.1.id', $zebra->id)
            ->where('partners.1.name', 'Zebra Partner')
            ->where('partners.1.image_url', '/storage/partners/zebra.png')
        );
});
