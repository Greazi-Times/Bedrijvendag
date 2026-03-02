<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CookiePolicySettings extends Settings
{
    public string $content;

    public static function group(): string
    {
        return 'cookies';
    }
}
