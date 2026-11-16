<?php

namespace App\Support\Forms;

/** Starting questions of the two global feedback templates. Administrators edit and publish them in the Forms resource. */
class DefaultFeedbackForms
{
    private const TEXT_LIMIT = 2000;

    private const NOTE = 'Please do not include personal information (such as names or contact details) in your answers.';

    /** @return array<string, mixed> */
    public static function student(): array
    {
        $yesMaybeNo = [['value' => 'yes', 'label' => 'Yes'], ['value' => 'maybe', 'label' => 'Maybe'], ['value' => 'no', 'label' => 'No']];

        return ['sections' => [['key' => 'feedback', 'title' => 'Your feedback', 'fields' => [
            self::rating('overall_rating', 'How would you rate the ATIx Bedrijvendag overall?', required: true),
            self::rating('company_variety', 'How satisfied were you with the variety of participating companies?'),
            self::rating('conversation_usefulness', 'How useful were your conversations with participating companies?', na: true),
            self::field('most_valuable', 'multiselect', 'Which parts of the event were most valuable?', [
                'description' => 'Select all that apply.',
                'options' => [
                    ['value' => 'meeting_companies', 'label' => 'Meeting companies'],
                    ['value' => 'internships', 'label' => 'Learning about internships'],
                    ['value' => 'careers', 'label' => 'Learning about career opportunities'],
                    ['value' => 'networking', 'label' => 'Networking'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
            ]),
            self::field('liked_most', 'textarea', 'What did you like most about the company fair?', ['max' => self::TEXT_LIMIT, 'description' => self::NOTE]),
            self::field('improve', 'textarea', 'What could we improve for the next edition?', ['max' => self::TEXT_LIMIT, 'description' => self::NOTE]),
            self::field('recommend', 'radio', 'Would you recommend attending the next company fair?', ['options' => $yesMaybeNo]),
        ]]]];
    }

    /** @return array<string, mixed> */
    public static function company(): array
    {
        $yesMaybeNo = [['value' => 'yes', 'label' => 'Yes'], ['value' => 'maybe', 'label' => 'Maybe'], ['value' => 'no', 'label' => 'No']];

        return ['sections' => [['key' => 'feedback', 'title' => 'Your feedback', 'fields' => [
            self::rating('overall_rating', 'How would you rate the company fair overall?', required: true),
            self::rating('communication', 'How satisfied were you with communication before the event?'),
            self::rating('organization', 'How would you rate the organization of the event?'),
            self::rating('stand_facilities', 'How satisfied were you with your stand and available facilities?', na: true),
            self::rating('student_interactions', 'How valuable were your interactions with students?'),
            self::field('worked_well', 'textarea', 'What worked particularly well?', ['max' => self::TEXT_LIMIT, 'description' => self::NOTE]),
            self::field('improve', 'textarea', 'What should we improve for future editions?', ['max' => self::TEXT_LIMIT, 'description' => self::NOTE]),
            self::field('participate_again', 'radio', 'Would your company consider participating again?', ['options' => $yesMaybeNo]),
        ]]]];
    }

    public static function for(string $kind): array
    {
        return $kind === 'feedback_company' ? self::company() : self::student();
    }

    /** @return array<string, mixed> */
    private static function rating(string $key, string $label, bool $required = false, bool $na = false): array
    {
        return self::field($key, 'rating', $label, ['required' => $required, 'max' => 5, 'allow_na' => $na, 'label_low' => 'Very dissatisfied', 'label_high' => 'Very satisfied']);
    }

    /** @return array<string, mixed> */
    private static function field(string $key, string $type, string $label, array $extra = []): array
    {
        return array_merge(['key' => $key, 'type' => $type, 'label' => $label, 'required' => false], $extra);
    }
}
