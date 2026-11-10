<?php

use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\CompanyAccessController;
use App\Http\Controllers\CompanyInterestController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactVerificationController;
use App\Http\Controllers\CookiePolicyController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventPublicMapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfServiceController;
use App\Models\BorrelEnrollment;
use App\Models\NewsletterSubscriber;
use App\Support\PageMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/locale', function (Request $request) {
    $validated = $request->validate([
        'locale' => ['required', Rule::in(['nl', 'en'])],
    ]);

    $request->session()->put('locale', $validated['locale']);
    App::setLocale($validated['locale']);

    return back();
})->name('locale.update');

Route::get('/plattegrond', [EventPublicMapController::class, 'show'])->name('map');

Route::get('/bedrijven', [CompaniesController::class, 'index'])->name('companies');

Route::get('/voor-bedrijven', [CompanyInterestController::class, 'create'])
    ->name('company-interest.create');

Route::post('/voor-bedrijven', [CompanyInterestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('company-interest.store');

Route::get('/bedrijf-toegang', [CompanyAccessController::class, 'create'])
    ->name('company-access.create');

Route::get('/bedrijf-toegang/zoek', [CompanyAccessController::class, 'match'])
    ->middleware('throttle:60,1')
    ->name('company-access.match');

Route::post('/bedrijf-toegang', [CompanyAccessController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('company-access.store');

Route::get('/bedrijf-profiel/{token}', [CompanyProfileController::class, 'edit'])
    ->name('company-profile.edit');

Route::post('/bedrijf-profiel/{token}', [CompanyProfileController::class, 'update'])
    ->middleware('throttle:10,1')
    ->name('company-profile.update');

Route::prefix('inschrijven/{token}')->middleware(\App\Http\Middleware\PrivatePage::class)->group(function () {
    Route::get('/', [EnrollmentController::class, 'show'])->middleware('throttle:60,1')->name('enrollment.show');
    Route::get('/bedrijven', [EnrollmentController::class, 'search'])->middleware('throttle:40,1')->name('enrollment.search');
    Route::get('/bedrijven/{company}', [EnrollmentController::class, 'company'])->middleware('throttle:40,1')->whereNumber('company')->name('enrollment.company');
    Route::post('/', [EnrollmentController::class, 'store'])->middleware('throttle:6,1')->name('enrollment.store');
    Route::get('/ontvangen', [EnrollmentController::class, 'received'])->middleware('throttle:60,1')->name('enrollment.received');
});

Route::prefix('contact-bevestigen/{token}')->middleware(\App\Http\Middleware\PrivatePage::class)->group(function () {
    Route::get('/', [ContactVerificationController::class, 'show'])->middleware('throttle:30,1')->name('contact.verify');
    Route::post('/', [ContactVerificationController::class, 'store'])->middleware('throttle:10,1')->name('contact.verify.store');
});

Route::prefix('informatie/{token}')->middleware(\App\Http\Middleware\PrivatePage::class)->group(function () {
    Route::get('/', [InformationController::class, 'show'])->middleware('throttle:60,1')->name('information.show');
    Route::post('/', [InformationController::class, 'store'])->middleware('throttle:10,1')->name('information.store');
});

// Private enrollment uploads: only staff who may view enrollments, always as a download.
Route::get('/dashboard-files/enrollments/{enrollment}/{key}', function (\App\Models\Enrollment $enrollment, string $key) {
    \Illuminate\Support\Facades\Gate::authorize('view', $enrollment);
    $file = $enrollment->files[$key] ?? abort(404);
    $disk = \Illuminate\Support\Facades\Storage::disk(\App\Services\EnrollmentWorkflow::PRIVATE_DISK);
    abort_unless($disk->exists($file['path']), 404);

    return $disk->download($file['path'], $file['name'], ['X-Content-Type-Options' => 'nosniff']);
})->middleware(['web', 'auth'])->name('enrollment.file');

Route::get('/dashboard-files/enrollment-info/{submission}/{key}', function (\App\Models\EnrollmentInfoSubmission $submission, string $key) {
    \Illuminate\Support\Facades\Gate::authorize('view', $submission->enrollment);
    $file = $submission->files[$key] ?? abort(404);
    $disk = \Illuminate\Support\Facades\Storage::disk(\App\Services\EnrollmentWorkflow::PRIVATE_DISK);
    abort_unless($disk->exists($file['path']), 404);

    return $disk->download($file['path'], $file['name'], ['X-Content-Type-Options' => 'nosniff']);
})->middleware(['web', 'auth'])->name('enrollment.info-file');

Route::get('/partners', [HomeController::class, 'partners'])->name('partners');

Route::get('/slides', function () {
    return Inertia::render('Slides', [
        'slideImages' => PageMedia::slideImages(),
    ]);
})->name('slides');

Route::get('/edities', [EventController::class, 'index'])->name('events');

Route::get('/edities/{event}', [EventController::class, 'show'])->name('edition.show');

Route::get('/over-ons', function () {
    return Inertia::render('About', [
        'aboutImages' => PageMedia::aboutImages(),
    ]);
})->name('about');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');

Route::get('/privacy-policy', PrivacyPolicyController::class)->name('privacy-policy');

Route::get('/terms-of-service', TermsOfServiceController::class)->name('terms-of-service');
Route::get('/cookie-policy', CookiePolicyController::class)->name('cookie-policy');

Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store']);
Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterSubscriptionController::class, 'showUnsubscribe'])->middleware('signed')->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{subscriber}', [NewsletterSubscriptionController::class, 'unsubscribe'])->middleware(['signed', 'throttle:20,1'])->name('newsletter.unsubscribe.store');

Route::get('/events/{event}/stands-pdf', [PdfController::class, 'standsPdf'])
    ->name('stands-pdf');

Route::post('/borrel-signup', function (Request $request) {
    $eventId = $request->input('event_id');

    $validated = $request->validate([
        'event_id' => [
            'required',
            'integer',
            Rule::exists('events', 'id')->where(fn ($q) => $q->whereDate('date', '>', now()->toDateString())),
        ],
        // Only an email address is collected. Promotional email is a separate, optional and unchecked-by-default choice.
        'marketing_consent' => ['nullable', 'boolean'],
        'email' => [
            'required',
            'email',
            'max:255',
            // The address is stored encrypted, so duplicates are found through its keyed lookup hash.
            fn (string $attribute, mixed $value, \Closure $fail) => BorrelEnrollment::query()->where('event_id', $eventId)->whereEmail('email', (string) $value)->exists()
                ? $fail(__('messages.borrel_duplicate'))
                : null,
        ],
    ], [
        'event_id.exists' => __('messages.borrel_unavailable'),
    ]);

    BorrelEnrollment::create(['event_id' => $validated['event_id'], 'email' => $validated['email']]);

    // Independent of the registration: kept (or not) on its own, and not touched by the event cleanup.
    if ($request->boolean('marketing_consent')) {
        NewsletterSubscriber::giveConsent($validated['email'], 'borrel');
    }

    return redirect()->back()->with('success', __('messages.borrel_success'));
});

// Unknown URLs still get the web middleware (session, locale) so the 404 page speaks the
// visitor's language. The dashboard is left to its own catch-all in DashboardPanelProvider.
Route::fallback(fn () => abort(404))->where('fallbackPlaceholder', '(?!dashboard(?:/|$)).*');
