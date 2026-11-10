<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use App\Notifications\SetupMechanicPassword;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Mechanics
{
    public function create(Workshop $workshop, User $actor, array $data): WorkshopMembership
    {
        try {
            return DB::transaction(function () use ($workshop, $actor, $data): WorkshopMembership {
                Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('create', [WorkshopMembership::class, $workshop]);
                $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(64)]);
                $user->forceFill(['needs_password_setup' => true])->save();
                $membership = new WorkshopMembership(['workshop_id' => $workshop->id, 'user_id' => $user->id, 'role' => 'mechanic']);
                $membership->save();

                return $membership->fresh('user');
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Email sudah terdaftar. Gunakan email baru.']);
        }
    }

    public function mutate(Workshop $workshop, User $actor, int $id, array $data, bool $activation): WorkshopMembership
    {
        return DB::transaction(function () use ($workshop, $actor, $id, $data, $activation): WorkshopMembership {
            Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            $membership = WorkshopMembership::where('workshop_id', $workshop->id)->where('role', 'mechanic')->whereKey($id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', [$membership, $workshop]);
            abort_unless($membership->version === (int) $data['version'], 409, 'Data mekanik berubah. Muat ulang halaman.');
            if ($activation) {
                if (! $data['is_active'] && WorkOrder::where('workshop_id', $workshop->id)->where('assigned_mechanic_id', $membership->user_id)
                    ->whereIn('status', ['draft', 'open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages(['mechanic' => 'Alihkan atau batalkan WO aktif mekanik sebelum menonaktifkan akses.']);
                }
                $membership->is_active = (bool) $data['is_active'];
            } else {
                abort_unless($membership->is_active && $membership->user->needs_password_setup, 409, 'Akun tidak menunggu pembuatan sandi atau aksesnya nonaktif.');
            }
            $membership->version++;
            $membership->save();

            return $membership->load('user');
        });
    }

    // Called only AFTER the account transaction committed. Never log transport exceptions/tokens.
    public function sendSetup(User $user): string
    {
        try {
            if ($this->usesLoggingTransport((string) config('mail.default'))) {
                throw new \LogicException('Password setup mail must not use a logging transport.');
            }
            $status = Password::sendResetLink(['email' => $user->email], function (User $recipient, string $token): void {
                $recipient->notify(new SetupMechanicPassword($token));
            });

            return $status === Password::ResetLinkSent
                ? 'Tautan pembuatan sandi telah dikirim. Berlaku 60 menit.'
                : 'Pengiriman dibatasi. Tunggu setidaknya 60 detik sebelum mencoba lagi.';
        } catch (\Throwable) {
            Log::warning('Mechanic password setup mail failed; use the resend action.');

            return 'Email gagal dikirim. Akun tetap menunggu pembuatan sandi. Periksa SMTP dan kirim ulang setelah 60 detik.';
        }
    }

    private function usesLoggingTransport(string $name, array $visited = []): bool
    {
        if (in_array($name, $visited, true)) {
            return true;
        }
        $mailer = config('mail.mailers.'.$name, []);
        if (($mailer['transport'] ?? null) === 'log') {
            return true;
        }
        foreach ($mailer['mailers'] ?? [] as $child) {
            if ($this->usesLoggingTransport($child, [...$visited, $name])) {
                return true;
            }
        }

        return false;
    }
}
