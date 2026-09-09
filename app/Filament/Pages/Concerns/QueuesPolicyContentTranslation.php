<?php

namespace App\Filament\Pages\Concerns;

use App\Support\PolicyContentTranslation;

trait QueuesPolicyContentTranslation
{
    protected function afterSave(): void
    {
        PolicyContentTranslation::queue(static::getSettings());
    }
}
