<?php

namespace App\Support\Forms;

/**
 * Fields the workflow reads by their `maps_to` value. Admins may relabel them but the form cannot be published
 * without them, so renaming "Company website" is safe and deleting it is not.
 */
class SystemFields
{
    public const KIND_ENROLLMENT = 'enrollment';

    public const KIND_INFO = 'info';

    /** @var array<string, list<string>> every one of these must stay in the form */
    public const REQUIRED = [
        self::KIND_ENROLLMENT => [
            'company.name', 'company.description', 'company.logo', 'company.website', 'company.educations', 'company.sectors',
            'contact.email',
            'invoice.contact_email', 'invoice.reference',
            'terms.accept',
        ],
        self::KIND_INFO => ['contact.email', 'lunch.participants', 'drinks.attending', 'promo.materials', 'company.verification'],
    ];

    /**
     * The invoice address may be one free-text question (older forms) or the structured set; at least one complete
     * alternative must be present.
     *
     * @var array<string, list<list<string>>>
     */
    public const REQUIRED_ONE_OF = [
        self::KIND_ENROLLMENT => [
            ['invoice.address'],
            ['invoice.street', 'invoice.postal_code', 'invoice.city', 'invoice.country'],
        ],
    ];

    /** Mapped but not mandatory. Names are no longer asked by default (the contact is identified by email address); forms that still ask for them keep working. */
    public const OPTIONAL = ['company.identity', 'lunch.count', 'invoice.contact_name', 'contact.name'];

    /** Mapped fields that must stay mandatory for the enrollment to be usable. */
    public const ALWAYS_REQUIRED = ['company.name', 'contact.email', 'terms.accept'];

    /** The question types each mapped property can safely take; anything else would break the workflow that reads it. */
    public const TYPES = [
        'company.name' => ['text'], 'company.description' => ['textarea', 'text'], 'company.logo' => ['file'], 'company.website' => ['url', 'text'],
        'company.educations' => ['multiselect'], 'company.sectors' => ['multiselect'],
        'contact.name' => ['text'], 'contact.email' => ['email'],
        'invoice.address' => ['textarea', 'text'], 'invoice.street' => ['text'], 'invoice.postal_code' => ['text'], 'invoice.city' => ['text'], 'invoice.country' => ['text', 'select'],
        'invoice.contact_name' => ['text'], 'invoice.contact_email' => ['email'], 'invoice.reference' => ['text'],
        'terms.accept' => ['terms'], 'lunch.participants' => ['repeater'], 'lunch.count' => ['number'], 'drinks.attending' => ['radio', 'select'],
        'promo.materials' => ['multiselect'],
        // "info" is the type older forms used for the profile check; new forms use the dedicated component.
        'company.verification' => ['profile_verification', 'info'], 'company.identity' => ['info'],
    ];

    /** Mapped option lists must keep reading the real tables, or ids would be accepted that do not exist. */
    public const OPTION_SOURCES = ['company.educations' => 'educations', 'company.sectors' => 'sectors'];

    /** @return list<string> */
    public static function allowedMaps(): array
    {
        $groups = array_merge(...array_values(array_map(fn (array $sets) => array_merge(...$sets), self::REQUIRED_ONE_OF)));

        return array_values(array_unique(array_merge(array_merge(...array_values(self::REQUIRED)), self::OPTIONAL, $groups)));
    }
}
