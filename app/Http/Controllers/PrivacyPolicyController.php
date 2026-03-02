<?php

namespace App\Http\Controllers;

use App\Settings\PrivacyPolicySettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PrivacyPolicyController extends Controller
{
    public function __invoke(PrivacyPolicySettings $settings)
    {
        $content = $settings->content ?? null;

        $table = config('settings.table', 'settings');

        $updatedAtRaw = DB::table($table)
            ->where('group', PrivacyPolicySettings::group())
            ->max('updated_at');

        $updatedAt = $updatedAtRaw
            ? Carbon::parse($updatedAtRaw)->timezone(config('app.timezone'))->format('F-d-Y')
            : null;

        return Inertia::render('legal/PrivacyPolicy', [
            'policy' => [
                'policyHtml' => $this->asHtml($content),
                'updatedAt' => $updatedAt,
            ],
        ]);
    }

    private function asHtml(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value) !== '' ? $value : null;
        }

        // If your model casts it to array/json, try common keys.
        if (is_array($value)) {
            $candidate = $value['html'] ?? $value['content'] ?? $value['value'] ?? null;

            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }

            // Fallback: if it is a simple list, join.
            $flat = collect($value)
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->implode("\n\n");

            return trim($flat) !== '' ? $flat : null;
        }

        return null;
    }
}
