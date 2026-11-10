<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrivacyAccountReview extends Command
{
    protected $signature = 'privacy:account-review {--confirm : Record that the review has been completed}';

    protected $description = 'List staff accounts for the yearly / after-edition review. Never deletes or disables anything';

    public function handle(): int
    {
        $last = DB::table('privacy_runs')->where('task', 'account_review')->where('dry_run', false)->max('created_at');
        $lastEnded = Event::query()->whereDate('date', '<', now(config('privacy.timezone'))->toDateString())->max('date');
        $due = ! $last || now()->subMonths((int) config('privacy.retention.account_review_months'))->gt($last) || ($lastEnded && $lastEnded > substr((string) $last, 0, 10));

        $this->table(['ID', 'Name', 'Roles', '2FA', 'Created'], User::query()->with('roles')->orderBy('id')->get()->map(fn (User $u): array => [
            $u->id, $u->name, $u->roles->pluck('name')->implode(', ') ?: '(no role)', $u->two_factor_confirmed_at ? 'yes' : 'no', $u->created_at?->toDateString(),
        ])->all());
        $this->line($due ? 'A review is due (yearly, or an edition has ended since the last one). Adjust roles or remove accounts in the admin Users page.' : 'No review is due.');

        if ($this->option('confirm')) {
            DB::table('privacy_runs')->insert(['task' => 'account_review', 'dry_run' => false, 'stats' => json_encode(['accounts' => User::count()]), 'created_at' => now()]);
            $this->info('Review recorded.');
        }

        return self::SUCCESS;
    }
}
