<?php

use App\Models\Event;
use App\Models\FeedbackResponse;
use App\Models\FeedbackSurvey;
use App\Services\FeedbackSurveys;
use App\Support\Availability;
use App\Support\Feedback\QrCode;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/** The model dates are immutable, so the clock must be set on both Carbon classes. */
function at(Carbon|string|null $moment): void
{
    Carbon::setTestNow($moment);
    \Carbon\CarbonImmutable::setTestNow($moment);
}

afterEach(fn () => at(null));

/** An Amsterdam wall-clock time as the date pickers hand it over: converted to the application timezone (UTC). */
function amsterdam(string $moment): Carbon
{
    return Carbon::parse($moment, 'Europe/Amsterdam')->utc();
}

/** Decodes a PNG with ZXing (the engine behind most phone scanners) at several sizes; null when python3 + zxing-cpp (`pip install zxing-cpp pillow opencv-python`) are unavailable. */
function decodeQr(string $png, array $sizes = [300, 600, 1024]): ?string
{
    $file = tempnam(sys_get_temp_dir(), 'qr').'.png';
    file_put_contents($file, $png);
    $script = 'import sys,cv2,zxingcpp
img=cv2.imread(sys.argv[1])
found=set()
for s in map(int, sys.argv[2:]):
    for r in zxingcpp.read_barcodes(cv2.resize(img,(s,s),interpolation=cv2.INTER_AREA)): found.add(r.text)
print("|".join(sorted(found)))';
    $out = @shell_exec('python3 -c '.escapeshellarg($script).' '.escapeshellarg($file).' '.implode(' ', $sizes).' 2>/dev/null');
    @unlink($file);

    return $out === null ? null : trim($out);
}

// ---------------------------------------------------------------- activation

test('feedback forms are switched off by default and each has its own switch', function () {
    $event = openEvent();

    expect($event->feedbackSurveys->every(fn ($s) => $s->enabled === false && $s->state() === 'disabled'))->toBeTrue();

    app(FeedbackSurveys::class)->setEnabled(survey($event), true);

    expect(survey($event)->state())->toBe('open')->and(survey($event, 'companies')->state())->toBe('disabled');
});

test('the shared availability rule matches the one enrollment uses', function (bool $enabled, ?string $opens, ?string $closes, string $state) {
    at('2027-11-19 12:00:00');

    expect(Availability::state($enabled, $opens ? Carbon::parse($opens) : null, $closes ? Carbon::parse($closes) : null))->toBe($state);
})->with([
    'off wins over a running window' => [false, '2027-11-01 00:00', '2027-12-01 00:00', 'disabled'],
    'on, no dates' => [true, null, null, 'open'],
    'on, opens later' => [true, '2027-11-20 00:00', null, 'not_open_yet'],
    'on, opened, no closing date' => [true, '2027-11-19 11:59', null, 'open'],
    'on, closed already' => [true, null, '2027-11-19 11:59', 'closed'],
    'on, inside the window' => [true, '2027-11-19 11:00', '2027-11-19 13:00', 'open'],
]);

test('the example from the requirements: student feedback opens by itself, company feedback stays closed until enabled', function () {
    $event = openEvent();
    $students = survey($event);
    $companies = survey($event, 'companies');
    $students->update(['enabled' => true, 'opens_at' => amsterdam('2027-11-19 13:00'), 'closes_at' => amsterdam('2027-11-26 23:59')]);
    $companies->update(['enabled' => false, 'opens_at' => amsterdam('2027-11-19 16:00'), 'closes_at' => amsterdam('2027-12-03 23:59')]);

    at(amsterdam('2027-11-19 12:59:59'));
    expect($students->fresh()->state())->toBe('not_open_yet');

    at(amsterdam('2027-11-19 13:00:00'));
    expect($students->fresh()->state())->toBe('open')->and($companies->fresh()->state())->toBe('disabled');
    $this->post(feedbackUrl($students), studentAnswers())->assertSessionHasNoErrors();
    $this->post(feedbackUrl($companies), ['answers' => ['overall_rating' => 5]])->assertSessionHasErrors('form');

    at(amsterdam('2027-11-27 00:00:00'));
    expect($students->fresh()->state())->toBe('closed');
    $this->post(feedbackUrl($students), studentAnswers())->assertSessionHasErrors('form');

    // Enabling the company form makes the (already running) window count; the dates were kept all along.
    at(amsterdam('2027-11-20 10:00:00'));
    app(FeedbackSurveys::class)->setEnabled($companies, true);
    expect($companies->fresh()->state())->toBe('open')->and($companies->fresh()->opens_at->equalTo(amsterdam('2027-11-19 16:00')))->toBeTrue();
    expect(FeedbackResponse::count())->toBe(1);
});

test('times entered in Amsterdam time switch correctly around the daylight saving change', function () {
    $survey = survey(openEvent());
    $survey->update(['enabled' => true, 'opens_at' => amsterdam('2027-03-28 03:00')]); // the clock jumps from 02:00 to 03:00 that night

    at(Carbon::parse('2027-03-28 00:59:59', 'UTC')); // 01:59:59 in Amsterdam
    expect($survey->fresh()->state())->toBe('not_open_yet');

    at(Carbon::parse('2027-03-28 01:00:00', 'UTC')); // 03:00:00 in Amsterdam
    expect($survey->fresh()->state())->toBe('open');
});

test('a form that is switched off while someone has it open rejects their submission and keeps its settings', function () {
    $survey = openSurvey(openEvent());
    $survey->update(['opens_at' => now()->subHour(), 'closes_at' => now()->addDay()]);
    $this->get(feedbackUrl($survey))->assertOk();

    app(FeedbackSurveys::class)->setEnabled($survey, false);

    $this->post(feedbackUrl($survey), studentAnswers())->assertSessionHasErrors('form');
    $this->get(feedbackUrl($survey))->assertNotFound();
    expect(FeedbackResponse::count())->toBe(0)->and($survey->fresh()->closes_at)->not->toBeNull();

    app(FeedbackSurveys::class)->setEnabled($survey, true);
    $this->post(feedbackUrl($survey), studentAnswers())->assertSessionHasNoErrors();
    expect(FeedbackResponse::count())->toBe(1);
});

test('opening and closing dates are optional, and a closing date cannot precede the opening date', function () {
    $survey = survey(openEvent());

    $survey->update(['enabled' => true, 'opens_at' => null, 'closes_at' => now()->addDay()]);
    expect($survey->fresh()->state())->toBe('open');
    $survey->update(['opens_at' => now()->subDay(), 'closes_at' => null]);
    expect($survey->fresh()->state())->toBe('open');

    expect(fn () => $survey->update(['opens_at' => now()->addDays(2), 'closes_at' => now()->addDay()]))->toThrow(ValidationException::class);
    expect(fn () => $survey->update(['opens_at' => now()->addDay(), 'closes_at' => now()->addDay()]))->toThrow(ValidationException::class);
});

test('creating an edition keeps both surveys independent and switched off, and existing settings survive a backfill', function () {
    $event = openEvent();
    survey($event)->update(['enabled' => true, 'opens_at' => now()->subDay()]);

    app(FeedbackSurveys::class)->backfill();
    $copy = Event::query()->whereKey($event->id)->first();

    expect(survey($copy)->enabled)->toBeTrue()->and(survey($copy)->opens_at)->not->toBeNull()->and(survey($copy, 'companies')->enabled)->toBeFalse();
});

// ---------------------------------------------------------------- QR codes

test('every survey has its own branded QR code that does not change when the form is switched on or off', function () {
    $event = openEvent();
    $students = survey($event);
    $companies = survey($event, 'companies');
    $other = survey(openEvent(['name' => 'Next', 'date' => '2028-03-01']));

    $svg = QrCode::svg($students->url());
    expect($svg)->toContain('<svg')->and($svg)->toContain(QrCode::ORANGE)->toContain('#3c83f6') // orange modules and the blue accent of the gear
        ->and(simplexml_load_string($svg))->not->toBeFalse()
        ->and(QrCode::svg($companies->url()))->not->toBe($svg)->and(QrCode::svg($other->url()))->not->toBe($svg);

    app(FeedbackSurveys::class)->setEnabled($students, true);
    app(FeedbackSurveys::class)->setEnabled($students, false);
    expect(QrCode::svg($students->fresh()->url()))->toBe($svg);
});

test('the branded QR code decodes to the exact public link at every size, also for long production URLs', function () {
    if (! extension_loaded('imagick') || decodeQr((string) QrCode::png('https://example.test/')) === null) {
        $this->markTestSkipped('Needs the imagick extension and python3 with zxing-cpp, opencv-python and pillow to decode the image.');
    }

    $event = openEvent();
    $urls = [
        survey($event)->url(),
        survey($event, 'companies')->url(),
        'https://bedrijvendag-atix.example.nl/feedback/2027/companies?utm_source=poster&utm_medium=qr&utm_campaign=bedrijvendag-2027&ref=printed-flyer-for-participating-companies',
    ];

    foreach ($urls as $url) {
        expect(decodeQr(QrCode::png($url), [250, 400, 700, 1024, 1400]))->toBe($url);
    }
});

test('the QR code has rounded modules, rounded finders with round centres and the official logo on a rounded plate', function () {
    $svg = QrCode::svg(survey(openEvent())->url());
    $icon = file_get_contents(resource_path('images/bedrijvendag-icon.svg'));

    expect(substr_count($svg, '<circle'))->toBe(3)                                       // round centre in each of the three finder patterns
        ->and(substr_count($svg, ' rx="'))->toBeGreaterThanOrEqual(7)                    // three rounded finders (2 rects each) + the plate
        ->and($svg)->toContain('a5 5 0 0 1 ')                                           // modules are drawn with circular corners, not squares
        ->and($svg)->toContain(QrCode::ORANGE)->toContain('fill="#fff"');
    preg_match('~<svg[^>]*>(.*)</svg>~s', $icon, $inner);
    expect($svg)->toContain($inner[1])                                                   // the supplied logo, unchanged
        ->and($icon)->toContain('#3c83f6')->and($icon)->toContain('#f60');               // its own orange and blue
});

test('staff can download valid SVG and PNG files of a survey QR code, the public cannot', function () {
    $survey = survey(openEvent());
    $this->get(route('feedback.qr', ['survey' => $survey, 'format' => 'svg']))->assertRedirect(); // guests go to the login

    feedbackAdmin();
    $svg = $this->get(route('feedback.qr', ['survey' => $survey, 'format' => 'svg']))->assertOk();
    expect($svg->headers->get('Content-Type'))->toBe('image/svg+xml')->and(simplexml_load_string($svg->getContent()))->not->toBeFalse();

    if (extension_loaded('imagick')) {
        $png = $this->get(route('feedback.qr', ['survey' => $survey, 'format' => 'png']))->assertOk();
        expect($png->headers->get('Content-Type'))->toBe('image/png')->and(substr($png->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    }
});

test('the QR code stays available while the form is switched off', function () {
    $survey = survey(openEvent());
    feedbackAdmin();

    expect($survey->state())->toBe('disabled');
    $this->get(route('feedback.qr', ['survey' => $survey, 'format' => 'svg']))->assertOk();
});

test('the survey model protects its relations from mass assignment', function () {
    $survey = survey(openEvent());

    $survey->fill(['event_id' => 999, 'audience' => 'companies', 'form_id' => 999]);

    expect($survey->getDirty())->toBe([]);
    expect(FeedbackSurvey::count())->toBe(2);
});
