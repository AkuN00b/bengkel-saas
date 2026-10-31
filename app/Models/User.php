<?php

namespace App\Models;

use App\Enums\WorkshopRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Workshop membership does not grant platform administration privileges.
     *
     * @return BelongsToMany<Workshop, $this>
     */
    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'workshop_user')
            ->using(WorkshopMembership::class)->withPivot(['id', 'role'])->withTimestamps();
    }

    public function isMemberOf(Workshop $workshop): bool
    {
        return $this->workshops()->whereKey($workshop->getKey())->exists();
    }

    public function hasWorkshopRole(Workshop $workshop, WorkshopRole $role): bool
    {
        return $this->workshops()->whereKey($workshop->getKey())->wherePivot('role', $role->value)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
