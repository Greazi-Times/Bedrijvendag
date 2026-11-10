<?php

namespace App\Support\Forms;

/** FROZEN copy of the forms every edition used to get generated. Only used to recognise untouched generated copies when editions move to the global defaults. Never edit. */
class LegacyDefaultForms
{
    /** @return array<string, mixed> */
    public static function enrollment(): array
    {
        return ['sections' => [
            self::section('company', 'Company information', [
                self::f('company_name', 'text', 'Company name', ['maps_to' => 'company.name', 'required' => true, 'help' => 'Start typing to find your company.']),
                self::f('company_description', 'textarea', 'Company description', ['maps_to' => 'company.description', 'required' => true, 'max' => 5000]),
                self::f('company_logo', 'file', 'Company logo', ['maps_to' => 'company.logo']),
                self::f('company_website', 'url', 'Company website', ['maps_to' => 'company.website', 'placeholder' => 'https://']),
                self::f('educations', 'multiselect', 'Educations', ['maps_to' => 'company.educations', 'options_source' => 'educations']),
                self::f('sectors', 'multiselect', 'Sectors', ['maps_to' => 'company.sectors', 'options_source' => 'sectors']),
            ]),
            self::section('contact', 'Primary contact', [
                self::f('contact_name', 'text', 'Contact person name', ['maps_to' => 'contact.name', 'required' => true]),
                self::f('contact_email', 'email', 'Contact person email', ['maps_to' => 'contact.email', 'required' => true]),
            ]),
            self::section('invoice', 'Invoice details', [
                self::f('invoice_address', 'textarea', 'Invoice address', ['maps_to' => 'invoice.address', 'required' => true, 'help' => 'Street, house number, postal code, city and country.', 'max' => 500]),
                self::f('invoice_contact_name', 'text', 'Invoice contact name', ['maps_to' => 'invoice.contact_name', 'required' => true]),
                self::f('invoice_contact_email', 'email', 'Invoice contact email', ['maps_to' => 'invoice.contact_email', 'required' => true]),
                self::f('invoice_reference', 'text', 'Invoice / purchase order reference', ['maps_to' => 'invoice.reference']),
            ]),
            self::section('terms', 'Terms and costs', [
                self::f('terms_accept', 'terms', 'I accept the terms and conditions and the participation fee', ['maps_to' => 'terms.accept']),
            ]),
        ]];
    }

    /** @return array<string, mixed> */
    public static function info(): array
    {
        $diet = [['value' => 'none', 'label' => 'No dietary requirements'], ['value' => 'vegetarian', 'label' => 'Vegetarian'], ['value' => 'vegan', 'label' => 'Vegan'], ['value' => 'halal', 'label' => 'Halal'], ['value' => 'gluten_free', 'label' => 'Gluten-free'], ['value' => 'lactose_free', 'label' => 'Lactose-free'], ['value' => 'other', 'label' => 'Other']];
        $promo = collect([
            'Small' => ['Desk materials', 'Merchandise', 'Brochures', 'Other small promotional materials'],
            'Medium' => ['Banners', 'Small prototypes', 'Medium promotional displays'],
            'Large' => ['Screens', 'Event walls', 'Medium or large prototypes', 'Other large equipment'],
        ])->flatMap(fn ($items, $size) => collect($items)->map(fn ($i) => ['value' => str($size.' '.$i)->slug('_')->toString(), 'label' => "{$size}: {$i}"]))->values()->all();

        return ['sections' => [
            self::section('contact', 'Contact', [
                self::f('contact_name', 'text', 'Contact person name', ['maps_to' => 'contact.name', 'required' => true]),
                self::f('contact_email', 'email', 'Contact person email', ['maps_to' => 'contact.email', 'required' => true]),
            ]),
            self::section('lunch', 'Lunch', [
                self::f('lunch_participants', 'repeater', 'Participants requiring lunch', [
                    'maps_to' => 'lunch.participants', 'max_items' => 3, 'max_items_setting' => 'max_lunch_participants',
                    'children' => [
                        self::f('lunch_name', 'text', 'Participant name', ['required' => true]),
                        self::f('lunch_diet', 'multiselect', 'Dietary preferences', ['required' => true, 'options' => $diet]),
                        self::f('lunch_diet_other', 'text', 'Other dietary requirements', ['required' => true, 'visible_if' => ['field' => 'lunch_diet', 'operator' => 'eq', 'value' => 'other']]),
                    ],
                ]),
            ]),
            self::section('drinks', 'Closing drinks reception', [
                self::f('drinks_attending', 'radio', 'Will your company participate in the closing drinks reception?', ['maps_to' => 'drinks.attending', 'required' => true, 'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No'], ['value' => 'unsure', 'label' => 'Not sure yet']]]),
                self::f('drinks_count', 'number', 'How many people will attend?', ['min' => 1, 'max' => 20, 'required' => true, 'visible_if' => ['field' => 'drinks_attending', 'operator' => 'eq', 'value' => 'yes']]),
            ]),
            self::section('promo', 'Promotional materials', [
                self::f('promo_materials', 'multiselect', 'What promotional materials will you bring?', ['maps_to' => 'promo.materials', 'options' => $promo]),
                self::f('promo_description', 'textarea', 'Please briefly describe the promotional materials and equipment you intend to bring.', ['max' => 2000]),
            ]),
            self::section('verify', 'Company details', [
                self::f('company_verification', 'info', 'Please verify the company information shown here', ['maps_to' => 'company.verification']),
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
