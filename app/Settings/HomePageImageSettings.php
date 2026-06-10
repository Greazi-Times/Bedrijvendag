<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HomePageImageSettings extends Settings
{
    public ?string $site_logo_path = null;

    public ?string $home_youtube_url = 'https://www.youtube.com/watch?v=yMBxJQk7gbg';

    public ?string $hero_image_path = null;

    public ?string $info_first_image_path = null;

    public ?string $info_second_image_path = null;

    public ?string $info_third_image_path = null;

    public ?string $about_hero_image_path = null;

    public ?string $about_info_first_image_path = null;

    public ?string $about_info_second_image_path = null;

    public ?string $about_info_third_image_path = null;

    /**
     * @var array<int, string>
     */
    public ?array $slide_image_paths = [];

    public static function group(): string
    {
        return 'home_images';
    }
}
