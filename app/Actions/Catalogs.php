<?php

namespace App\Actions;

use App\Models\Service;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class Catalogs
{
    public function save(Workshop $workshop, User $actor, string $class, ?int $id, array $data, bool $activation = false): Model
    {
        abort_unless(in_array($class, [Service::class, SparePart::class], true), 404);
        try {
            return DB::transaction(function () use ($workshop, $actor, $class, $id, $data, $activation): Model {
                Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('create', [$class, $workshop]);
                $record = $id ? $class::where('workshop_id', $workshop->id)->whereKey($id)->lockForUpdate()->firstOrFail() : new $class;
                if ($id) {
                    Gate::forUser($actor)->authorize('update', [$record, $workshop]);
                    abort_unless($record->version === (int) $data['version'], 409, 'Katalog atau stok telah berubah. Muat ulang halaman.');
                    $record->version++;
                } else {
                    $record->forceFill(['workshop_id' => $workshop->id, 'version' => 1]);
                }
                if ($activation) {
                    $record->is_active = (bool) $data['is_active'];
                } else {
                    $fields = $class === Service::class ? ['name', 'description', 'default_price'] : ['name', 'sku', 'selling_price', 'minimum_stock'];
                    $record->forceFill(array_intersect_key($data, array_flip($fields)));
                    if ($class === SparePart::class) {
                        if ($id && isset($data['unit']) && $data['unit'] !== $record->unit) {
                            throw ValidationException::withMessages(['unit' => 'Satuan barang tidak dapat diubah. Buat barang baru untuk satuan berbeda.']);
                        }
                        if (! $id) {
                            $record->forceFill(['unit' => $data['unit'], 'current_stock' => 0]);
                        }
                    }
                }
                $record->save();

                return $record->fresh();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['sku' => 'SKU sudah terdaftar di bengkel ini.']);
        }
    }
}
