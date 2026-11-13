<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\Widget;
use Livewire\Attributes\Locked;

/** Definitions, formulas and data limitations for a section, so the numbers are never read as more than they are. */
class NotesWidget extends Widget
{
    public const NOTES = [
        'overview' => [
            'Deze pagina toont de geselecteerde editie. "Goedgekeurd" bevestigt een inschrijving, niet de werkelijke aanwezigheid.',
            'Verwachte deelnamekosten zijn bij inschrijving geaccepteerde bedragen, geen ontvangen betalingen.',
            'Vergelijkingen verwijzen naar de editie die direct aan de geselecteerde editie voorafgaat (of de gekozen vergelijkingseditie).',
        ],
        'enrollments' => [
            'Goedkeurings- en afwijzingspercentages gebruiken beoordeelde inschrijvingen (goedgekeurd + afgewezen) als noemer.',
            'De filters Status en Periode gelden voor de trendgrafieken; de cijfers hierboven tellen altijd alle statussen.',
            'De wachtrij in de tijd wordt gereconstrueerd uit indien- en beoordelingsdatum.',
        ],
        'retention' => [
            'Deelname: goedgekeurde inschrijvingen vanaf de eerste editie met inschrijvingen; eerdere edities gebruiken standtoewijzingen als bewijs van deelname. Een stand zonder goedgekeurde inschrijving telt dan niet mee.',
            'Nieuw: eerste deelname ooit. Terugkerend: deed eerder mee. Behouden: ook in de direct voorgaande editie. Gereactiveerd: eerder, maar niet in de voorgaande editie. Verloren: wel in de voorgaande, niet in de geselecteerde editie.',
            'Retentie = behouden / deelnemers vorige editie × 100%. Churn = verloren / deelnemers vorige editie × 100%. Een aankomende of lopende editie is voorlopig: de deelnemerslijst is niet definitief.',
            'Bedrijven worden herkend op bedrijfs-ID, niet op naam. Cohortcellen voor edities die nog niet bestaan of voorlopig zijn blijven leeg.',
        ],
        'stands' => [
            'Bedrijfs- en partnerstands worden apart geteld. Toewijzingsregels en geschiktheid zijn niet gewijzigd; het tweede formulier heeft geen invloed.',
            'Historische toewijzingen zonder goedgekeurde inschrijving blijven ongewijzigd en tellen niet als deelname.',
        ],
        'finance' => [
            'Alle bedragen zijn geaccepteerde deelnamekosten (verwacht). Er is geen factuur-, betaal- of sponsorstatus in het systeem, dus die worden niet getoond.',
            'Bedragen in verschillende valuta worden nooit opgeteld; grafieken tonen de meest voorkomende valuta. Edities zonder inschrijvingen hebben geen vastgelegde kosten.',
        ],
        'catering' => [
            'Cijfers komen uit de laatste versie van het tweede formulier per goedgekeurd bedrijf; eerdere versies worden niet opgeteld.',
            'Dieetwensen kunnen overlappen en zijn anoniem. Ze worden kort na de editie verwijderd (bewaartermijn); daarna zijn ze niet beschikbaar, en dat is niet hetzelfde als nul.',
            'Promotiemateriaal wordt ingedeeld op de opgeslagen optiewaarden (small_/medium_/large_, screen, prototype), niet op vertaalde labels.',
        ],
        'taxonomy' => [
            'Bedrijven kunnen meerdere sectoren of opleidingen hebben; de aantallen per categorie tellen daarom op tot meer dan het aantal bedrijven.',
            'Sectoren en opleidingen komen uit het huidige bedrijfsprofiel, niet uit een momentopname per editie.',
        ],
        'comparison' => [
            'Verschil in procenten wordt niet getoond (n.v.t.) als de uitgangswaarde nul is. "Niet vastgelegd" betekent dat de gegevens voor die editie niet zijn bewaard of nooit zijn verzameld.',
            'Het verschil bij percentages is in procentpunten (pp).',
        ],
    ];

    protected string $view = 'filament.widgets.analytics.notes';

    protected int|string|array $columnSpan = 'full';

    #[Locked]
    public string $section = 'overview';

    protected function getViewData(): array
    {
        return ['notes' => self::NOTES[$this->section] ?? []];
    }
}
