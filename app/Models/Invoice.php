<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['total' => 'integer', 'version' => 'integer', 'issued_at' => 'immutable_datetime', 'voided_at' => 'immutable_datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(InvoiceEvent::class);
    }

    public function paidAmount(): int
    {
        return (int) $this->payments()->where('workshop_id', $this->workshop_id)->whereDoesntHave('reversal')->sum('amount');
    }

    public function paymentLabel(?int $paid = null): string
    {
        if ($this->status !== 'issued') {
            return $this->status === 'void' ? 'Tidak berlaku' : 'Belum terbit';
        }
        $paid ??= $this->paidAmount();

        return $paid === $this->total ? ($this->total === 0 ? 'Lunas—tanpa pembayaran' : 'Lunas') : ($paid === 0 ? 'Belum dibayar' : 'Dibayar sebagian');
    }

    public function label(): string
    {
        return $this->number ?? 'Draft #'.$this->id;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft','issued' => 'Terbit','void' => 'Dibatalkan'
        };
    }
}
