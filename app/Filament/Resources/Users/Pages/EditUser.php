<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendPasswordReset')
                ->label('Send password reset link')
                ->icon(Heroicon::OutlinedKey)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn (User $record): string => "{$record->email} receives a link to choose a new password.")
                ->authorize('update')
                ->action(fn (User $record) => $this->sendPasswordReset($record)),
            DeleteAction::make()
                ->modalDescription('The account is removed permanently. Records it reviewed keep their history without a reviewer.')
                ->using(function (User $record): bool {
                    Access::deleteUser(auth()->user(), $record);

                    return true;
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->getRecord()->roles()->pluck('roles.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return DB::transaction(function () use ($record, $data): User {
            $record->fill(Arr::only($data, ['name', 'email']));

            $changes = collect($record->getDirty())
                ->map(fn ($new, string $key): array => ['old' => $record->getOriginal($key), 'new' => $new])
                ->all();

            $record->save();

            if ($changes) {
                AuditLog::record('user.updated', $record, $changes);
            }

            // The roles field is disabled (so not submitted) when editing yourself.
            if (array_key_exists('roles', $data)) {
                Access::syncRoles(auth()->user(), $record, $data['roles']);
            }

            return $record;
        });
    }

    private function sendPasswordReset(User $user): void
    {
        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            ['email' => $user->email],
            function (User $user, string $token): void {
                $notification = app(ResetPassword::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                $user->notify($notification);
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            Notification::make()->title(__($status))->danger()->send();

            return;
        }

        AuditLog::record('user.password_reset_sent', $user);

        Notification::make()->title('Password reset link sent')->success()->send();
    }
}
