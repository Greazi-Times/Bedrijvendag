<?php

use App\Models\BorrelEnrollment;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventPublicMapController;
use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\CookiePolicyController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/plattegrond', [EventPublicMapController::class, 'show'])->name('map');

Route::get('/bedrijven', [CompaniesController::class, 'index'])->name('companies');

Route::get('/partners', [HomeController::class, 'partners'])->name('partners');

Route::get('/edities', [EventController::class, 'index'])->name('events');

Route::get('/edities/{event}', [EventController::class, 'show'])->name('edition.show');

Route::get('/over-ons', function () {
    return \Inertia\Inertia::render('About');
})->name('about');

Route::get('/contact', function () {
    return \Inertia\Inertia::render('Contact');
})->name('contact');

Route::get('/privacy-policy', PrivacyPolicyController::class)->name('privacy-policy');

Route::get('/terms-of-service', TermsOfServiceController::class)->name('terms-of-service');
Route::get('/cookie-policy', CookiePolicyController::class)->name('cookie-policy');

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
