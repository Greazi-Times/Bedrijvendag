<?php

use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\CookiePolicyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventPublicMapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfServiceController;
use App\Models\BorrelEnrollment;
use App\Support\PageMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/plattegrond', [EventPublicMapController::class, 'show'])->name('map');

Route::get('/bedrijven', [CompaniesController::class, 'index'])->name('companies');

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

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::get('/privacy-policy', PrivacyPolicyController::class)->name('privacy-policy');

Route::get('/terms-of-service', TermsOfServiceController::class)->name('terms-of-service');
Route::get('/cookie-policy', CookiePolicyController::class)->name('cookie-policy');

Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store']);

Route::get('/events/{event}/stands-pdf', [PdfController::class, 'standsPdf'])
    ->name('stands-pdf');

Route::post('/borrel-signup', function (Request $request) {
    $eventId = $request->input('event_id');

    $validated = $request->validate([
        'event_id' => ['required', 'integer', 'exists:events,id'],
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required',
            'email',
            'max:255',
            Rule::unique('borrel_enrollments', 'email')
                ->where(fn ($q) => $q->where('event_id', $eventId)),
        ],
    ], [
        'email.unique' => 'Dit e-mailadres is al aangemeld voor deze borrel.',
    ]);

    BorrelEnrollment::create($validated);

    return redirect()->back()->with('success', 'Je bent aangemeld. Tot bij de borrel.');
});
