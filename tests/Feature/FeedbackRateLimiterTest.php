<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Finder\Finder;

/**
 * Production caches its routes (`route:cache`). Then no route file is executed, so a limiter registered in a route file
 * does not exist and `throttle:feedback` throws MissingRateLimiterException (HTTP 500). These tests therefore also boot
 * a FRESH PHP process with cached routes; tests that only run in this process cannot see that failure.
 */
test('the feedback limiter is registered by the application itself, 120 requests per minute per IP address', function () {
    $limit = RateLimiter::limiter('feedback')(Request::create('/feedback/1/students', 'GET', server: ['REMOTE_ADDR' => '203.0.113.7']));

    expect($limit->maxAttempts)->toBe(120)->and($limit->decaySeconds)->toBe(60)->and($limit->key)->toBe('203.0.113.7');
});

test('no named rate limiter is registered inside a route file', function () {
    $offenders = collect(Finder::create()->files()->in(base_path('routes'))->name('*.php'))
        ->filter(fn ($file) => str_contains($file->getContents(), 'RateLimiter::for'))->map->getFilename()->values()->all();

    expect($offenders)->toBe([]); // route files are skipped when routes are cached
});

test('a fresh process with cached routes still has the feedback limiter and the public routes', function () {
    $cache = tempnam(sys_get_temp_dir(), 'routes-').'.php'; // never touches bootstrap/cache of the real application
    $run = fn (array $args) => Process::path(base_path())->env(['APP_ROUTES_CACHE' => $cache])->timeout(120)->run([PHP_BINARY, 'artisan', ...$args]);

    try {
        expect($run(['route:cache'])->successful())->toBeTrue();

        $result = $run(['tinker', '--execute=echo json_encode(["cached" => app()->routesAreCached(), "limiter" => Illuminate\Support\Facades\RateLimiter::limiter("feedback") !== null, "show" => Route::has("feedback.show"), "store" => Route::has("feedback.store")]);']);
        $json = json_decode(trim(collect(explode("\n", trim($result->output())))->last()), true);

        expect($json)->toBe(['cached' => true, 'limiter' => true, 'show' => true, 'store' => true]);
    } finally {
        @unlink($cache);
    }
});

test('exceeding the limit answers HTTP 429 for anonymous visitors, not a server error, and the counter holds no personal data', function () {
    $survey = openSurvey(openEvent());
    $key = md5('feedback'.'127.0.0.1'); // what the throttle middleware derives from the limiter name and the IP key

    $this->get(feedbackUrl($survey))->assertOk(); // no login involved

    foreach (range(1, 120) as $unused) {
        RateLimiter::hit($key, 60);
    }

    $this->get(feedbackUrl($survey))->assertStatus(429);
    $this->post(feedbackUrl($survey), studentAnswers())->assertStatus(429);
    expect(App\Models\FeedbackResponse::count())->toBe(0);

    RateLimiter::clear($key);
    $this->post(feedbackUrl($survey), studentAnswers())->assertSessionHasNoErrors();
    expect(App\Models\FeedbackResponse::count())->toBe(1);
});
