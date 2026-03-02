<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('privacy.content', '<p>Schrijf hier je privacy policy…</p>');
    }
};
