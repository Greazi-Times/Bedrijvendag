<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Access;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // One transaction: a rejected role assignment also undoes the new account.
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create(Arr::only($data, ['name', 'email', 'password']));

            AuditLog::record('user.created', $user, ['email' => $user->email]);

            Access::syncRoles(auth()->user(), $user, $data['roles'] ?? []);

            return $user;
        });
    }
}
