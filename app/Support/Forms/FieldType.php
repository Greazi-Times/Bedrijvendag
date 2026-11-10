<?php

namespace App\Support\Forms;

enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Email = 'email';
    case Phone = 'phone';
    case Url = 'url';
    case Number = 'number';
    case Date = 'date';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case File = 'file';
    case Repeater = 'repeater';
    case Info = 'info';
    case Heading = 'heading';
    case Terms = 'terms';
    case ProfileVerification = 'profile_verification';

    public function hasOptions(): bool
    {
        return in_array($this, [self::Radio, self::Select, self::Multiselect], true);
    }

    /** Layout-only types never produce an answer. */
    public function isDisplayOnly(): bool
    {
        return in_array($this, [self::Info, self::Heading, self::ProfileVerification], true);
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'text' => 'Short text', 'textarea' => 'Long text', 'email' => 'Email', 'phone' => 'Phone number',
            'url' => 'URL', 'number' => 'Number', 'date' => 'Date', 'checkbox' => 'Checkbox',
            'radio' => 'Radio group', 'select' => 'Single select', 'multiselect' => 'Multi-select',
            'file' => 'File / image upload', 'repeater' => 'Repeater (multiple entries)',
            'info' => 'Informational text', 'heading' => 'Heading', 'terms' => 'Terms and conditions acceptance',
            'profile_verification' => 'Company profile verification (system component)',
        ];
    }
}
