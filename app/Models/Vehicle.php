<?php

namespace App\Models;

use App\Support\PlateNumber;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'plate_number', 'brand', 'model', 'year', 'notes'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['year' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Vehicle $vehicle): void {
            if (is_string($vehicle->plate_number)) {
                $vehicle->plate_number = PlateNumber::display($vehicle->plate_number);
                $vehicle->normalized_plate_number = PlateNumber::normalize($vehicle->plate_number);
            }
        });
    }

    /** @return BelongsTo<Workshop, $this> */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
