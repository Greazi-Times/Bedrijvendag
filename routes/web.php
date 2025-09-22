<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('geschiedenis', function () {
    return Inertia::render('History');
})->name('history');

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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('avans', function () {
    return Inertia::render('AvansHogeschool');
})->name('avans');


Route::get('privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

Route::get('terms-of-service', function () {
    return Inertia::render('TermsOfService');
})->name('terms-of-service');

Route::get('cookie-policy', function () {
    return Inertia::render('CookiePolicy');
})->name('cookie-policy');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
