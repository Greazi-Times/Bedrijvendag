<?php

use App\Http\Controllers\FeedbackController;
use App\Http\Middleware\PrivatePage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::prefix('feedback/{event}/{audience}')->whereNumber('event')->whereIn('audience', ['students', 'companies'])
    ->middleware(['web', PrivatePage::class])->group(function () {
        Route::get('/', [FeedbackController::class, 'show'])->middleware('throttle:feedback')->name('feedback.show');
        Route::post('/', [FeedbackController::class, 'store'])->middleware('throttle:feedback')->name('feedback.store');
    });

// The QR code of a survey, for staff who may view feedback. Downloaded, never embedded elsewhere.
Route::get('/dashboard-files/feedback/{survey}/qr.{format}', function (\App\Models\FeedbackSurvey $survey, string $format) {
    Gate::authorize('view', $survey);
    $name = "feedback-{$survey->audience}-{$survey->event_id}.{$format}";

    return match ($format) {
        'svg' => response(\App\Support\Feedback\QrCode::svg($survey->url()), 200, ['Content-Type' => 'image/svg+xml', 'Content-Disposition' => "attachment; filename=\"{$name}\""]),
        'png' => ($png = \App\Support\Feedback\QrCode::png($survey->url())) ? response($png, 200, ['Content-Type' => 'image/png', 'Content-Disposition' => "attachment; filename=\"{$name}\""]) : abort(404),
        default => abort(404),
    };
})->whereIn('format', ['svg', 'png'])->middleware(['web', 'auth'])->name('feedback.qr');
