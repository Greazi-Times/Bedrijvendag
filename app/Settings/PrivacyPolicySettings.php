<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PrivacyPolicySettings extends Settings
{
    public string $content;

    public static function group(): string
    {
        return 'privacy';
    }
}
