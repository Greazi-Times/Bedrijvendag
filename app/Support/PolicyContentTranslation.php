<?php

namespace App\Support;

use App\Jobs\TranslatePolicyContent;
use App\Services\DeepLTranslator;
use App\Settings\CookiePolicySettings;
use App\Settings\PrivacyPolicySettings;
use App\Settings\TermsOfServiceSettings;

class PolicyContentTranslation
{
    /** @var list<class-string> */
    public const SETTINGS_CLASSES = [
        PrivacyPolicySettings::class,
        TermsOfServiceSettings::class,
        CookiePolicySettings::class,
    ];

    public static function supports(string $settingsClass): bool
    {
        return in_array($settingsClass, self::SETTINGS_CLASSES, true);
    }

    public static function queue(string $settingsClass, bool $force = false): bool
    {
        if (! self::supports($settingsClass) || ! app(DeepLTranslator::class)->configured()) {
            return false;
        }

        $settings = app($settingsClass);
        $source = trim((string) $settings->content);

        if ($source === '') {
            return false;
        }

        $sourceHash = TranslationContent::hash($source);

        if (! $force && filled($settings->content_en) && $settings->content_en_source_hash === $sourceHash) {
            return false;
        }

        TranslatePolicyContent::dispatch($settingsClass, $sourceHash)->afterResponse();

        return true;
    }
}
