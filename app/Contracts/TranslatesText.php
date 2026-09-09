<?php

namespace App\Contracts;

interface TranslatesText
{
    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translate(array $texts, string $sourceLocale, string $targetLocale, bool $html = false): array;
}
