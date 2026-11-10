<?php

namespace App\Http\Controllers;

use App\Actions\Inventory;
use App\Http\Requests\StockMovementRequest;
use App\Models\InventoryMovement;

class StockController extends Controller
{
    private function id(string $id): int
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);

        return (int) $id;
    }

    public function store(StockMovementRequest $request, string $catalog, Inventory $action)
    {
        $action->move($request->attributes->get('activeWorkshop'), $request->user(), $this->id($catalog), $request->validated(), $request->validated('type'));

        return to_route('spare-parts.show', $catalog)->with('status', 'Transaksi stok berhasil dicatat.');
    }

    public function returns(StockMovementRequest $request, string $catalog, string $movement, Inventory $action)
    {
        $partId = $this->id($catalog);
        $source = InventoryMovement::where('workshop_id', $request->attributes->get('activeWorkshop')->id)->where('spare_part_id', $partId)
            ->where('type', 'work_order_usage')->whereKey($this->id($movement))->firstOrFail();
        $action->move($request->attributes->get('activeWorkshop'), $request->user(), $partId, $request->validated(), 'work_order_return',
            $source->work_order_id, $source->work_order_spare_part_id, $source->id, true);

        return to_route('spare-parts.show', $catalog)->with('status', 'Retur stok dicatat. WO final tetap terkunci; nilai WO tidak berubah.');
    }
}
