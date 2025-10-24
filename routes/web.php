<?php

use App\Http\Controllers\EditionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('editions', function () {
    return Inertia::render('Editions');
})->name('editions');

Route::get('bedrijven', function () {
    return Inertia::render('Companies');
})->name('companies');

Route::get('partners', function () {
    return Inertia::render('Partners');
})->name('partners');

Route::get('over-ons', function () {
    return Inertia::render('AboutUs');
})->name('about-us');

Route::get('contact', function () {
    return Inertia::render('Contact');
})->name('contact');


Route::middleware(['auth', 'verified', 'editorOrAdmin'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Company routes
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('createCompany');
        Route::post('/companies/store', [CompanyController::class, 'store'])->name('storeCompany');
        Route::get('/companies/{id}', [CompanyController::class, 'show'])->name('showCompany');
        Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('editCompany');

        // Edition routes
        Route::get('/editions', [EditionController::class, 'index'])->name('dashEditions');
        Route::get('/editions/create', [EditionController::class, 'create'])->name('createEdition');
        Route::post('/editions/store', [EditionController::class, 'store'])->name('storeEdition');
        Route::get('/editions/{edition}', [EditionController::class, 'show'])->name('showEdition');
        Route::get('/editions/{edition}/edit', [EditionController::class, 'edit'])->name('editEdition');
    });

Route::get('privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

Route::get('terms-of-service', function () {
    return Inertia::render('TermsOfService');
})->name('terms-of-service');

Route::get('cookie-policy', function () {
    return Inertia::render('CookiePolicy');
})->name('cookie-policy');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
