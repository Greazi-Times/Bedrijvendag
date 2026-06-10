<?php

namespace App\Support;

use App\Settings\HomePageImageSettings;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

class PageMedia
{
    public static function siteLogo(): string
    {
        try {
            $settings = app(HomePageImageSettings::class);

            return self::imageUrl($settings->site_logo_path, '/favicon.svg');
        } catch (MissingSettings) {
            return '/favicon.svg';
        }
    }

    public static function homeImages(): array
    {
        $fallbacks = [
            'hero' => '/images/hero.png',
            'infoFirst' => '/images/info-1.jpg',
            'infoSecond' => '/images/info-2.jpg',
            'infoThird' => '/images/info-3.jpg',
        ];

        try {
            $settings = app(HomePageImageSettings::class);

            return [
                'hero' => self::imageUrl($settings->hero_image_path, $fallbacks['hero']),
                'infoFirst' => self::imageUrl($settings->info_first_image_path, $fallbacks['infoFirst']),
                'infoSecond' => self::imageUrl($settings->info_second_image_path, $fallbacks['infoSecond']),
                'infoThird' => self::imageUrl($settings->info_third_image_path, $fallbacks['infoThird']),
            ];
        } catch (MissingSettings) {
            return $fallbacks;
        }
    }

    public static function aboutImages(): array
    {
        $fallbacks = [
            'hero' => '/images/info-7.jpg',
            'infoFirst' => '/images/info-4.jpg',
            'infoSecond' => '/images/info-5.jpg',
            'infoThird' => '/images/info-6.jpg',
        ];

        try {
            $settings = app(HomePageImageSettings::class);

            return [
                'hero' => self::imageUrl($settings->about_hero_image_path, $fallbacks['hero']),
                'infoFirst' => self::imageUrl($settings->about_info_first_image_path, $fallbacks['infoFirst']),
                'infoSecond' => self::imageUrl($settings->about_info_second_image_path, $fallbacks['infoSecond']),
                'infoThird' => self::imageUrl($settings->about_info_third_image_path, $fallbacks['infoThird']),
            ];
        } catch (MissingSettings) {
            return $fallbacks;
        }
    }

    public static function slideImages(): array
    {
        $fallbacks = [
            '/images/slides/Bedrijvendag-maart-26-2026-3.png',
            '/images/slides/Bedrijvendag-maart-26-2026-4.png',
            '/images/slides/Bedrijvendag-maart-26-2026-5.png',
            '/images/slides/Bedrijvendag-maart-26-2026-6.png',
            '/images/slides/Bedrijvendag-maart-26-2026-7.png',
        ];

        try {
            $settings = app(HomePageImageSettings::class);
            $paths = is_array($settings->slide_image_paths)
                ? array_values(array_filter($settings->slide_image_paths))
                : [];

            if ($paths === []) {
                return $fallbacks;
            }

            return array_map(fn (string $path) => self::imageUrl($path, $path), $paths);
        } catch (MissingSettings) {
            return $fallbacks;
        }
    }

    public static function homeYoutubeUrl(): string
    {
        try {
            $settings = app(HomePageImageSettings::class);

            return filled($settings->home_youtube_url)
                ? $settings->home_youtube_url
                : 'https://www.youtube.com/watch?v=yMBxJQk7gbg';
        } catch (MissingSettings) {
            return 'https://www.youtube.com/watch?v=yMBxJQk7gbg';
        }
    }

    private static function imageUrl(?string $path, string $fallback): string
    {
        if (! $path) {
            return $fallback;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
