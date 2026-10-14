<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrantSuperAdmin extends Command
{
    protected $signature = 'users:grant-super-admin {email : Email address of an existing user}';

    protected $description = 'Give one explicitly named existing user the Super Admin role';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user with that email address exists.');

            return self::FAILURE;
        }

        if ($user->isSuperAdmin()) {
            $this->info("{$user->name} is already a Super Admin.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Give {$user->name} <{$user->email}> unrestricted access to the dashboard?")) {
            return self::FAILURE;
        }

        Permissions::sync();

        DB::transaction(function () use ($user): void {
            $user->assignRole(Permissions::SUPER_ADMIN);

            AuditLog::record('user.super_admin_granted', $user, ['via' => 'console']);
        });

        $this->info("{$user->name} is now a Super Admin.");

        return self::SUCCESS;
    }
}
