<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ([
            'home_images.site_logo_path' => null,
            'home_images.home_youtube_url' => 'https://www.youtube.com/watch?v=yMBxJQk7gbg',
            'home_images.about_hero_image_path' => null,
            'home_images.about_info_first_image_path' => null,
            'home_images.about_info_second_image_path' => null,
            'home_images.about_info_third_image_path' => null,
            'home_images.slide_image_paths' => [],
        ] as $property => $value) {
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, $value);
            }
        }
    }
};
