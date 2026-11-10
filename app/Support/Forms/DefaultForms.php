<?php

namespace App\Support\Forms;

/** The content of the two global default forms. Administrators edit and publish them in the Forms resource. */
class DefaultForms
{
    /** @return array<string, mixed> */
    public static function enrollment(): array
    {
        return ['sections' => [
            self::section('company', 'Company information', [
                self::f('company_name', 'text', 'Company name', ['maps_to' => 'company.name', 'required' => true, 'description' => 'Start typing to find your company. Not listed? Just fill in your company name.']),
                self::f('company_description', 'textarea', 'Company description', ['maps_to' => 'company.description', 'required' => true, 'max' => 5000]),
                self::f('company_logo', 'file', 'Company logo', ['maps_to' => 'company.logo', 'description' => 'PNG, JPG or WebP, up to 4 MB.']),
                self::f('company_website', 'url', 'Company website', ['maps_to' => 'company.website', 'placeholder' => 'https://']),
                self::f('educations', 'multiselect', 'Educations', ['maps_to' => 'company.educations', 'options_source' => 'educations']),
                self::f('sectors', 'multiselect', 'Sectors', ['maps_to' => 'company.sectors', 'options_source' => 'sectors']),
            ]),
            self::section('contact', 'Contact', [
                self::f('contact_email', 'email', 'Contact email address', ['maps_to' => 'contact.email', 'required' => true, 'description' => 'We send the confirmation and the invitation for the information form to this address.']),
            ]),
            self::section('invoice', 'Invoice information', [
                self::f('invoice_street', 'text', 'Street and house number', ['maps_to' => 'invoice.street', 'required' => true]),
                self::f('invoice_postal_code', 'text', 'Postal code', ['maps_to' => 'invoice.postal_code', 'required' => true, 'max' => 20]),
                self::f('invoice_city', 'text', 'City', ['maps_to' => 'invoice.city', 'required' => true]),
                self::f('invoice_country', 'text', 'Country', ['maps_to' => 'invoice.country', 'required' => true, 'default' => 'Nederland']),
                self::f('invoice_contact_email', 'email', 'Invoice email address', ['maps_to' => 'invoice.contact_email', 'required' => true, 'description' => 'A shared mailbox such as the finance department works best.']),
                self::f('invoice_reference', 'text', 'Invoice reference / purchase order number', ['maps_to' => 'invoice.reference']),
            ]),
            self::section('terms', 'Participation fee and terms', [
                self::f('terms_accept', 'terms', 'I accept the terms and conditions and the participation fee', ['maps_to' => 'terms.accept', 'description' => 'The fee and the terms shown come from this edition. This is not a payment; you will receive an invoice later.']),
            ]),
        ]];
    }

    /** @return array<string, mixed> */
    public static function info(): array
    {
        $diet = [['value' => 'none', 'label' => 'No dietary requirements'], ['value' => 'vegetarian', 'label' => 'Vegetarian'], ['value' => 'vegan', 'label' => 'Vegan'], ['value' => 'halal', 'label' => 'Halal'], ['value' => 'gluten_free', 'label' => 'Gluten-free'], ['value' => 'lactose_free', 'label' => 'Lactose-free'], ['value' => 'other', 'label' => 'Other']];
        $promo = collect([
            'Small' => ['Desk materials', 'Merchandise', 'Brochures'],
            'Medium' => ['Banners', 'Small prototypes', 'Medium promotional displays'],
            'Large' => ['Screens', 'Event walls', 'Medium or large prototypes'],
        ])->flatMap(fn ($items, $size) => collect($items)->map(fn ($i) => ['value' => str($size.' '.$i)->slug('_')->toString(), 'label' => "{$size}: {$i}"]))->values()->all();

        return ['sections' => [
            self::section('contact', 'Company and contact', [
                self::f('company_identity', 'info', 'Company', ['maps_to' => 'company.identity', 'description' => 'This form is linked to your approved company and cannot be pointed at another one.']),
                // Prefilled with the address from the enrollment; only used to confirm or change where we write about the day itself.
                self::f('contact_email', 'email', 'Contact email address', ['maps_to' => 'contact.email', 'required' => true, 'description' => 'Where should we write about the day itself? A different address is only used after it has been confirmed.']),
            ]),
            self::section('lunch', 'Lunch', [
                self::f('lunch_count', 'number', 'How many participants will have lunch?', ['maps_to' => 'lunch.count', 'min' => 0, 'max_setting' => 'max_lunch_participants', 'description' => 'The maximum for this edition applies.']),
                self::f('lunch_participants', 'repeater', 'Dietary requirements per lunch participant', [
                    'maps_to' => 'lunch.participants', 'max_items' => 3, 'max_items_setting' => 'max_lunch_participants', 'count_from' => 'lunch_count',
                    'children' => [
                        self::f('lunch_diet', 'multiselect', 'Dietary preferences', ['required' => true, 'options' => $diet]),
                        self::f('lunch_diet_other', 'text', 'Other dietary requirements', ['required' => true, 'visible_if' => ['field' => 'lunch_diet', 'operator' => 'eq', 'value' => 'other']]),
                    ],
                ]),
            ]),
            self::section('drinks', 'Closing drinks reception', [
                self::f('drinks_attending', 'radio', 'Will your company participate in the closing drinks reception?', ['maps_to' => 'drinks.attending', 'required' => true, 'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No'], ['value' => 'unsure', 'label' => 'Not sure yet']]]),
                self::f('drinks_count', 'number', 'How many people will attend?', ['min' => 1, 'max' => 20, 'visible_if' => ['field' => 'drinks_attending', 'operator' => 'eq', 'value' => 'yes']]),
            ]),
            self::section('promo', 'Promotional materials', [
                self::f('promo_materials', 'multiselect', 'What promotional materials will you bring?', ['maps_to' => 'promo.materials', 'options' => $promo]),
                self::f('promo_description', 'textarea', 'Please briefly describe the promotional materials and equipment you intend to bring.', ['max' => 2000]),
            ]),
            self::section('verify', 'Company details', [
                self::f('company_verification', 'profile_verification', 'Are your company details still correct?', ['maps_to' => 'company.verification', 'required' => true, 'description' => 'Check what we show about your company. Any changes are reviewed by the organisers before they are published.']),
            ]),
        ]];
    }

    /** @return array<string, mixed> */
    private static function section(string $key, string $title, array $fields): array
    {
        return ['key' => $key, 'title' => $title, 'fields' => $fields];
    }

    /** @return array<string, mixed> */
    private static function f(string $key, string $type, string $label, array $extra = []): array
    {
        return array_merge(['key' => $key, 'type' => $type, 'label' => $label, 'required' => false], $extra);
    }
}
