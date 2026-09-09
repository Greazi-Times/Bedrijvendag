<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['privacy', 'tos', 'cookies'] as $group) {
            $this->migrator->add("{$group}.content_en", '');
            $this->migrator->add("{$group}.content_en_source_hash", '');
        }
    }
};
