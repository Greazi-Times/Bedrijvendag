<?php

use App\Http\Controllers\BorrelController;
//use App\Http\Controllers\ContactController;
use App\Http\Controllers\EditionController;
use App\Http\Controllers\StandController;
use App\Http\Controllers\StandPdfController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CompanyController;

Route::get('/', [WelcomeController::class, 'index'])->name('home');

Route::get('editions', [EditionController::class, 'index'])->name('editions');

Route::get('bedrijven', [CompanyController::class, 'publicIndex'])->name('companies');

Route::get('partners', function () { return Inertia::render('Partners'); })->name('partners');

Route::get('over-ons', function () { return Inertia::render('AboutUs'); })->name('about-us');

Route::get('contact', function () { return Inertia::render('Contact'); })->name('contact');
//Route::post('/contact/send', [ContactController::class, 'send'])->name('contactSend');

Route::post('/borrel-enrollment', [BorrelController::class, 'store'])->name('storeBorrel');

Route::middleware(['auth', 'verified', 'editorOrAdmin'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Company routes
        Route::get('/company/create', [CompanyController::class, 'create'])->name('createCompany');
        Route::post('/company/store', [CompanyController::class, 'store'])->name('storeCompany');
        Route::get('/company/{company}', [CompanyController::class, 'show'])->name('showCompany');
        Route::get('/company/{company}/edit', [CompanyController::class, 'edit'])->name('editCompany');
        Route::put('/company/{company}/update', [CompanyController::class, 'update'])->name('updateCompany');
        Route::put('/company/{company}/remove', [CompanyController::class, 'destroy'])->name('removeCompany');

        // Edition routes
        Route::get('/editions', [EditionController::class, 'index2'])->name('dashEditions');
        Route::get('/edition/create', [EditionController::class, 'create'])->name('createEdition');
        Route::post('/edition/store', [EditionController::class, 'store'])->name('storeEdition');
        Route::get('/edition/{edition}', [EditionController::class, 'show'])->name('showEdition');
        Route::get('/edition/{edition}/edit', [EditionController::class, 'edit'])->name('editEdition');
        Route::put('/edition/{edition}/update', [EditionController::class, 'update'])->name('updateEdition');
        Route::put('/edition/{edition}/remove', [EditionController::class, 'destroy'])->name('removeEdition');

        // Stand routes
        Route::get('/stands', [StandController::class, 'index'])->name('dashStands');
        Route::patch('/stands/{stand}', [StandController::class, 'update'])->name('updateStands');
        Route::get('/stands/pdf', [StandPdfController::class, 'downloadAll'])->name('createStandPDF');

    });

Route::get('plattegrond', [StandController::class, 'view'])->name('plattegrond');

Route::get('privacy-policy', function () {
    return Inertia::render('legal/PrivacyPolicy');
})->name('privacy-policy');

Route::get('terms-of-service', function () {
    return Inertia::render('legal/TermsOfService');
})->name('terms-of-service');

Route::get('cookie-policy', function () {
    return Inertia::render('legal/CookiePolicy');
})->name('cookie-policy');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
