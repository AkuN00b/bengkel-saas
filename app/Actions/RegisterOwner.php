<?php

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Enums\WorkshopRole;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterOwner
{
    /** @param array{name: string, email: string, password: string, workshop_name: string} $data */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $plan = Plan::where('code', 'trial')->where('is_active', true)
                ->where('price_monthly', 0)->sharedLock()->first();

            if ($plan === null) {
                throw ValidationException::withMessages([
                    'workshop_name' => 'Pendaftaran trial belum tersedia. Silakan coba lagi nanti.',
                ]);
            }

            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $workshop = Workshop::create([
                'name' => $data['workshop_name'],
                'slug' => (Str::limit(Str::slug($data['workshop_name']), 80, '') ?: 'bengkel').'-'.Str::lower(Str::random(16)),
            ]);
            $workshop->users()->attach($user, ['role' => WorkshopRole::Owner]);
            $start = now()->toImmutable()->startOfSecond();
            $workshop->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Trialing,
                'starts_at' => $start,
                'trial_ends_at' => $start->addDays(14),
                'ends_at' => $start->addDays(14),
            ]);

            return $user;
        });
    }
}
