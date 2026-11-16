<?php

use App\Settings\HomePageImageSettings;
use App\Support\PageMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function saveSlides(array $paths): void
{
    $settings = app(HomePageImageSettings::class);
    $settings->slide_image_paths = $paths;
    $settings->save();
}

function slideVersionNow(): string
{
    return test()->getJson('/slides/version')->assertOk()->json('version');
}

beforeEach(function () {
    // Some rows are created lazily by the Filament page, so make sure all exist.
    $names = ['site_logo_path', 'event_map_path', 'hero_image_path', 'info_first_image_path', 'info_second_image_path', 'info_third_image_path', 'about_hero_image_path', 'about_info_first_image_path', 'about_info_second_image_path', 'about_info_third_image_path', 'home_youtube_url', 'slide_image_paths'];
    DB::table('settings')->upsert(
        array_map(fn ($n) => ['group' => 'home_images', 'name' => $n, 'locked' => false, 'payload' => 'null', 'created_at' => now(), 'updated_at' => now()], $names),
        ['group', 'name'],
        ['updated_at'],
    );
    Cache::flush();
    saveSlides(['slides/a.png', 'slides/b.png']);
});

it('changes the version on create, edit, delete and reorder', function () {
    $before = slideVersionNow();

    saveSlides(['slides/a.png', 'slides/b.png', 'slides/c.png']);
    expect($v = slideVersionNow())->not->toBe($before);
    $created = $v;

    saveSlides(['slides/a.png', 'slides/x.png', 'slides/c.png']);
    expect($v = slideVersionNow())->not->toBe($created);
    $edited = $v;

    saveSlides(['slides/c.png', 'slides/x.png', 'slides/a.png']);
    expect($v = slideVersionNow())->not->toBe($edited);
    $reordered = $v;

    saveSlides(['slides/c.png']);
    expect(slideVersionNow())->not->toBe($reordered);
});

it('keeps the version when unrelated settings change', function () {
    $v = slideVersionNow();

    $settings = app(HomePageImageSettings::class);
    $settings->home_youtube_url = 'https://www.youtube.com/watch?v=other';
    $settings->save();

    expect(slideVersionNow())->toBe($v);
});

it('returns the same version to multiple clients and the same value after cache expiry', function () {
    $v = slideVersionNow();

    expect(slideVersionNow())->toBe($v);
    Cache::flush();
    expect(slideVersionNow())->toBe($v);
});

it('does not publish a version for a rolled back save', function () {
    $v = slideVersionNow();

    try {
        DB::transaction(function () {
            saveSlides(['slides/rolled-back.png']);
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(slideVersionNow())->toBe($v);
});

it('answers a cached version poll with a single cheap query and no session write', function () {
    slideVersionNow();

    DB::enableQueryLog();
    $response = $this->getJson('/slides/version')->assertOk();
    DB::disableQueryLog();

    expect(DB::getQueryLog())->toHaveCount(0); // array cache store in tests; production database cache = 1 SELECT
    expect($response->headers->get('Cache-Control'))->toContain('no-cache')
        ->and($response->headers->has('Set-Cookie'))->toBeFalse();
});

it('serves the slide list with the matching version', function () {
    $this->getJson('/slides/data')->assertOk()->assertJson([
        'version' => PageMedia::slideVersion(),
        'images' => PageMedia::slideImages(),
    ]);

    $this->get('/slides')->assertInertia(fn ($page) => $page->where('slideVersion', PageMedia::slideVersion()));
});
