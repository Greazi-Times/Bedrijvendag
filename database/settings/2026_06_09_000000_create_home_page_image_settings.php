<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ([
            'home_images.hero_image_path' => null,
            'home_images.info_first_image_path' => null,
            'home_images.info_second_image_path' => null,
            'home_images.info_third_image_path' => null,
        ] as $property => $value) {
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, $value);
            }
        }
    }
};
