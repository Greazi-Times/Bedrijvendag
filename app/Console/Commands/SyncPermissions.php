<?php

namespace App\Console\Commands;

use App\Support\Permissions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Create the permissions and system roles defined in App\Support\Permissions (safe to repeat)';

    public function handle(): int
    {
        Permissions::sync();

        $this->info(count(Permissions::all()).' permissions and '.count(Permissions::SYSTEM_ROLES).' system roles are in place.');

        $unused = Permission::query()->whereNotIn('name', Permissions::all())->pluck('name');

        if ($unused->isNotEmpty()) {
            $this->warn('Not defined in code anymore (left untouched): '.$unused->implode(', '));
        }

        return self::SUCCESS;
    }
}
