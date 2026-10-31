<?php

namespace App\Models;

use App\Enums\WorkshopRole;
use Database\Factories\WorkshopMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['workshop_id', 'user_id', 'role'])]
class WorkshopMembership extends Pivot
{
    /** @use HasFactory<WorkshopMembershipFactory> */
    use HasFactory;

    protected $table = 'workshop_user';

    public $incrementing = true;

    protected function casts(): array
    {
        return ['role' => WorkshopRole::class];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
