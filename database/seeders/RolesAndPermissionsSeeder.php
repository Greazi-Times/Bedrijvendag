<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;

/** Safe to run repeatedly; never assigns roles to users. */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permissions::sync();
    }
}
