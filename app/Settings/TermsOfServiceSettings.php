<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TermsOfServiceSettings extends Settings
{
    public string $content;

    public static function group(): string
    {
        return 'tos';
    }
}
