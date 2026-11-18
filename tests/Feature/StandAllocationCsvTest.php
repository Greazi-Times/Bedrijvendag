<?php

require_once __DIR__.'/../Support/AnalyticsFixtures.php';

use App\Models\Company;
use App\Models\EventStand;

function csvStand($event, string $number, ?Company $company = null, string $type = 'company'): void
{
    EventStand::query()->forceCreate(['event_id' => $event->id, 'company_id' => $company?->id, 'type' => $type, 'stand_number' => $number]);
}

it('exports all stands of the selected edition, naturally sorted, with empty names for free stands', function () {
    $event = analyticsEdition('Editie A', '2027-03-01');
    $other = analyticsEdition('Editie B', '2028-03-01');
    $acme = Company::create(['name' => 'Café Ünï']);
    $evil = Company::create(['name' => '=SUM(A1)']);

    analyticsEnroll($event, $acme);
    analyticsEnroll($event, $evil);
    analyticsEnroll($other, $acme);

    csvStand($event, '10', $acme);
    csvStand($event, '2');
    csvStand($event, '1', $evil);
    csvStand($event, '1', null, 'partner');
    csvStand($other, '5', $acme);

    $this->actingAs(reviewerWithout(['event-stands.view']));
    $response = $this->get(route('stands-csv', $event))->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('stand-allocations-editie-a.csv');

    $content = $response->streamedContent();
    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();
    expect(substr($content, 3))->toBe("\"Stand Number\";\"Company Name\"\n1;'=SUM(A1)\n2;\n10;\"Café Ünï\"\nP1;\n"
    );
});

it('returns only the header for an edition without stands', function () {
    $event = analyticsEdition('Leeg', '2027-03-01');
    $this->actingAs(reviewerWithout(['event-stands.view']));

    expect(substr($this->get(route('stands-csv', $event))->streamedContent(), 3))->toBe("\"Stand Number\";\"Company Name\"\n");
});

it('is refused without the event map permission and for guests', function () {
    $event = analyticsEdition('Editie A', '2027-03-01');

    $this->get(route('stands-csv', $event))->assertRedirect();

    $this->actingAs(reviewerWithout(['companies.view']));
    $this->get(route('stands-csv', $event))->assertForbidden();
});

it('refuses the stands PDF for guests and users without the event map permission', function () {
    $event = analyticsEdition('Editie A', '2027-03-01');

    $this->get(route('stands-pdf', $event))->assertRedirect();

    $this->actingAs(reviewerWithout(['companies.view']));
    $this->get(route('stands-pdf', $event))->assertForbidden();
});
