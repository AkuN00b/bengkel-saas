<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('default_price');
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestampsTz();
            $table->unique(['workshop_id', 'id']);
            $table->index(['workshop_id', 'is_active', 'id']);
        });
        Schema::create('spare_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->string('sku', 100)->nullable();
            $table->string('name');
            $table->string('unit', 20)->default('pcs');
            $table->bigInteger('selling_price');
            $table->bigInteger('current_stock')->default(0);
            $table->bigInteger('minimum_stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestampsTz();
            $table->unique(['workshop_id', 'id']);
            $table->unique(['workshop_id', 'sku']);
            $table->index(['workshop_id', 'is_active', 'id']);
        });
        foreach (['work_order_services' => 'service', 'work_order_spare_parts' => 'spare_part'] as $name => $catalog) {
            Schema::create($name, function (Blueprint $table) use ($catalog): void {
                $table->id();
                $table->unsignedBigInteger('workshop_id');
                $table->unsignedBigInteger('work_order_id');
                $table->unsignedBigInteger($catalog.'_id');
                $table->string('name_snapshot');
                $table->text('description_snapshot')->nullable();
                $table->integer('quantity');
                $table->bigInteger('unit_price');
                $table->bigInteger('subtotal');
                $table->string('status', 20);
                $table->timestampsTz();
                if ($catalog === 'spare_part') {
                    $table->string('sku_snapshot', 100)->nullable();
                    $table->string('unit_snapshot', 20);
                    $table->unique(['workshop_id', 'id', 'spare_part_id', 'work_order_id'], 'wo_parts_movement_reference');
                }
                $table->foreign(['workshop_id', 'work_order_id'])->references(['workshop_id', 'id'])->on('work_orders')->restrictOnDelete()->restrictOnUpdate();
                $table->foreign(['workshop_id', $catalog.'_id'])->references(['workshop_id', 'id'])->on($catalog === 'service' ? 'services' : 'spare_parts')->restrictOnDelete()->restrictOnUpdate();
                $table->index(['work_order_id', 'id']);
            });
        }
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('spare_part_id');
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->unsignedBigInteger('work_order_spare_part_id')->nullable();
            $table->unsignedBigInteger('source_movement_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->integer('quantity');
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->text('reason')->nullable();
            $table->string('name_snapshot');
            $table->string('sku_snapshot', 100)->nullable();
            $table->string('unit_snapshot', 20);
            $table->uuid('operation_key');
            $table->string('request_fingerprint', 64);
            $table->timestampTz('created_at');
            $table->unique(['workshop_id', 'operation_key']);
            $table->unique(['workshop_id', 'id', 'spare_part_id', 'work_order_spare_part_id', 'work_order_id'], 'inventory_source_reference');
            $table->foreign(['workshop_id', 'spare_part_id'])->references(['workshop_id', 'id'])->on('spare_parts')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['workshop_id', 'work_order_spare_part_id', 'spare_part_id', 'work_order_id'], 'inventory_line_foreign')
                ->references(['workshop_id', 'id', 'spare_part_id', 'work_order_id'])->on('work_order_spare_parts')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['workshop_id', 'source_movement_id', 'spare_part_id', 'work_order_spare_part_id', 'work_order_id'], 'inventory_source_foreign')
                ->references(['workshop_id', 'id', 'spare_part_id', 'work_order_spare_part_id', 'work_order_id'])->on('inventory_movements')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['workshop_id', 'spare_part_id', 'id']);
            $table->index('source_movement_id');
        });
        DB::statement('ALTER TABLE services ADD CONSTRAINT services_valid CHECK (length(trim(name)) > 0 AND default_price >= 0 AND version > 0)');
        DB::statement('ALTER TABLE spare_parts ADD CONSTRAINT spare_parts_valid CHECK (length(trim(name)) > 0 AND length(trim(unit)) > 0 AND selling_price >= 0 AND current_stock >= 0 AND minimum_stock >= 0 AND version > 0 AND (sku IS NULL OR (sku = upper(trim(sku)) AND length(sku) > 0)))');
        foreach (['work_order_services' => "'active','cancelled'", 'work_order_spare_parts' => "'planned','used','cancelled'"] as $table => $states) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_valid CHECK (quantity > 0 AND unit_price >= 0 AND subtotal >= 0 AND subtotal::numeric = quantity::numeric * unit_price::numeric AND status IN ({$states}))");
        }
        DB::statement("CREATE UNIQUE INDEX inventory_one_usage_per_line ON inventory_movements (work_order_spare_part_id) WHERE type = 'work_order_usage'");
        DB::statement("ALTER TABLE inventory_movements ADD CONSTRAINT inventory_valid CHECK (
            quantity > 0 AND balance_before >= 0 AND balance_after >= 0
            AND type IN ('restock','work_order_usage','work_order_return','adjustment_in','adjustment_out')
            AND balance_after::numeric = balance_before::numeric + CASE WHEN type IN ('work_order_usage','adjustment_out') THEN -quantity::numeric ELSE quantity::numeric END
            AND (type NOT IN ('adjustment_in','adjustment_out','work_order_return') OR (reason IS NOT NULL AND length(trim(reason)) > 0))
            AND ((type IN ('work_order_usage','work_order_return')) = (work_order_spare_part_id IS NOT NULL AND work_order_id IS NOT NULL))
            AND (type IN ('work_order_usage','work_order_return') OR (work_order_id IS NULL AND work_order_spare_part_id IS NULL))
            AND ((type = 'work_order_return') = (source_movement_id IS NOT NULL)))");
        DB::unprepared("CREATE OR REPLACE FUNCTION prevent_inventory_mutation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Inventory movements are append-only'; END; $$");
        DB::unprepared('CREATE TRIGGER inventory_movements_immutable BEFORE UPDATE OR DELETE ON inventory_movements FOR EACH ROW EXECUTE FUNCTION prevent_inventory_mutation()');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        DB::statement('DROP FUNCTION IF EXISTS prevent_inventory_mutation()');
        Schema::dropIfExists('work_order_spare_parts');
        Schema::dropIfExists('work_order_services');
        Schema::dropIfExists('spare_parts');
        Schema::dropIfExists('services');
    }
};
