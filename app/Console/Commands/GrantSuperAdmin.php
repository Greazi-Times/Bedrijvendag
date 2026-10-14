<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
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

            Audit::log('roles.assigned', "Role Super Admin assigned to {$user->name} from the console", $user, [
                'roles' => [Permissions::SUPER_ADMIN],
                'via' => 'console',
            ], severity: 'high');
        });

        $this->info("{$user->name} is now a Super Admin.");

        return self::SUCCESS;
    }
}
