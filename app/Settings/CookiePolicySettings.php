<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CookiePolicySettings extends Settings
{
    public string $content;

    public string $content_en;

    public string $content_en_source_hash;

    public static function group(): string
    {
        return 'cookies';
    }
}
