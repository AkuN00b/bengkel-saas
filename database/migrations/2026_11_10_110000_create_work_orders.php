<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', fn (Blueprint $table) => $table->bigInteger('next_work_order_number')->default(1));
        Schema::table('vehicles', fn (Blueprint $table) => $table->unique(['workshop_id', 'id']));
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreignId('assigned_mechanic_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('number', 32);
            $table->string('status', 20);
            $table->text('complaint')->nullable();
            $table->text('notes')->nullable();
            $table->string('plate_number_snapshot', 20)->nullable();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_phone_snapshot', 32)->nullable();
            $table->string('mechanic_name_snapshot')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            foreach (['opened_at', 'started_at', 'completed_at', 'cancelled_at'] as $column) {
                $table->timestampTz($column)->nullable();
            }
            $table->timestampsTz();
            $table->unique(['workshop_id', 'number']);
            $table->unique(['workshop_id', 'id']);
            $table->index(['workshop_id', 'status', 'id']);
            $table->index(['workshop_id', 'assigned_mechanic_id', 'status']);
            $table->index(['workshop_id', 'customer_id']);
            $table->foreign(['workshop_id', 'vehicle_id'])->references(['workshop_id', 'id'])->on('vehicles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['workshop_id', 'customer_id'])->references(['workshop_id', 'id'])->on('customers')->restrictOnDelete()->restrictOnUpdate();
        });
        DB::statement("CREATE UNIQUE INDEX work_orders_one_active_vehicle ON work_orders (workshop_id, vehicle_id) WHERE status IN ('draft', 'open', 'in_progress')");
        DB::statement("ALTER TABLE work_orders ADD CONSTRAINT work_orders_status CHECK (status IN ('draft','open','in_progress','completed','cancelled'))");
        DB::statement('ALTER TABLE work_orders ADD CONSTRAINT work_orders_version CHECK (version > 0)');
        DB::statement("ALTER TABLE work_orders ADD CONSTRAINT work_orders_lifecycle CHECK (
            (status <> 'draft' OR (opened_at IS NULL AND started_at IS NULL))
            AND (status NOT IN ('open','in_progress','completed') OR (opened_at IS NOT NULL AND plate_number_snapshot IS NOT NULL))
            AND (status NOT IN ('in_progress','completed') OR (started_at IS NOT NULL AND assigned_mechanic_id IS NOT NULL))
            AND ((status = 'completed') = (completed_at IS NOT NULL))
            AND ((status = 'cancelled') = (cancelled_at IS NOT NULL))
            AND (status <> 'cancelled' OR length(trim(cancellation_reason)) > 0 AND cancellation_reason IS NOT NULL)
            AND (started_at IS NULL OR started_at >= opened_at)
            AND (completed_at IS NULL OR completed_at >= started_at))");
        Schema::create('work_order_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('work_order_id');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->jsonb('details');
            $table->timestampTz('created_at');
            $table->foreign(['workshop_id', 'work_order_id'])->references(['workshop_id', 'id'])->on('work_orders')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['work_order_id', 'id']);
        });
        DB::unprepared("CREATE OR REPLACE FUNCTION prevent_work_order_event_mutation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Work order events are append-only'; END; $$");
        DB::unprepared('CREATE TRIGGER work_order_events_immutable BEFORE UPDATE OR DELETE ON work_order_events FOR EACH ROW EXECUTE FUNCTION prevent_work_order_event_mutation()');
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_events');
        DB::statement('DROP FUNCTION IF EXISTS prevent_work_order_event_mutation()');
        Schema::dropIfExists('work_orders');
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropUnique(['workshop_id', 'id']));
        Schema::table('workshops', fn (Blueprint $table) => $table->dropColumn('next_work_order_number'));
    }
};
