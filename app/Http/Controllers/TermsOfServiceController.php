<?php

namespace App\Http\Controllers;

use App\Settings\TermsOfServiceSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TermsOfServiceController extends Controller
{
    public function __invoke(TermsOfServiceSettings $settings)
    {
        $content = app()->getLocale() === 'en' && filled($settings->content_en)
            ? $settings->content_en
            : $settings->content;

        if (is_string($content) && $content !== '') {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            $content = null;
        }

        $table = config('settings.table', 'settings');

        $updatedAtRaw = DB::table($table)
            ->where('group', TermsOfServiceSettings::group())
            ->where('name', 'content')
            ->max('updated_at');

        $updatedAt = $updatedAtRaw
            ? Carbon::parse($updatedAtRaw)->timezone(config('app.timezone'))->toDateString()
            : null;

        return Inertia::render('legal/TermsOfService', [
            'policy' => [
                'policyHtml' => $content,
                'updatedAt' => $updatedAt,
            ],
        ]);
    }
}
