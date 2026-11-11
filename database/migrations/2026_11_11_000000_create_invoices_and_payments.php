<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', fn (Blueprint $table) => $table->bigInteger('next_invoice_number')->default(1));
        foreach (['work_order_services', 'work_order_spare_parts'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->unique(['workshop_id', 'work_order_id', 'id'], $name.'_invoice_source'));
        }
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedBigInteger('replaces_invoice_id')->nullable();
            $table->string('number', 32)->nullable();
            $table->string('status', 10)->default('draft');
            $table->bigInteger('total')->default(0);
            $table->integer('version')->default(1);
            $table->string('source_fingerprint', 64);
            $table->string('work_order_number', 32);
            $table->string('plate_number_snapshot', 20);
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_phone_snapshot', 32)->nullable();
            $table->string('workshop_name_snapshot');
            $table->string('workshop_phone_snapshot')->nullable();
            $table->text('workshop_address_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestampsTz();
            $table->unique(['workshop_id', 'number']);
            $table->unique(['workshop_id', 'id']);
            $table->unique(['workshop_id', 'id', 'work_order_id'], 'invoices_source_reference');
            $table->foreign(['workshop_id', 'work_order_id'])->references(['workshop_id', 'id'])->on('work_orders')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['workshop_id', 'replaces_invoice_id', 'work_order_id'], 'invoices_replaces_foreign')->references(['workshop_id', 'id', 'work_order_id'])->on('invoices')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['workshop_id', 'status', 'id']);
        });
        DB::statement("CREATE UNIQUE INDEX invoices_one_current_per_wo ON invoices (workshop_id,work_order_id) WHERE status IN ('draft','issued')");
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_valid CHECK (status IN ('draft','issued','void') AND total >= 0 AND version > 0
            AND ((number IS NOT NULL) = (issued_at IS NOT NULL)) AND ((issued_at IS NOT NULL) = (issued_by IS NOT NULL))
            AND (status <> 'draft' OR issued_at IS NULL) AND (status <> 'issued' OR issued_at IS NOT NULL)
            AND ((status = 'void') = (voided_at IS NOT NULL)) AND ((status = 'void') = (voided_by IS NOT NULL))
            AND (status <> 'void' OR (void_reason IS NOT NULL AND length(trim(void_reason)) > 0)))");
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('work_order_service_id')->nullable();
            $table->unsignedBigInteger('work_order_spare_part_id')->nullable();
            $table->string('item_type', 15);
            $table->string('name_snapshot');
            $table->text('description_snapshot')->nullable();
            $table->string('unit_snapshot', 20)->nullable();
            $table->integer('quantity');
            $table->bigInteger('unit_price');
            $table->bigInteger('subtotal');
            $table->text('price_reason')->nullable();
            $table->timestampsTz();
            $table->foreign(['workshop_id', 'invoice_id', 'work_order_id'], 'invoice_items_parent_foreign')->references(['workshop_id', 'id', 'work_order_id'])->on('invoices')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['workshop_id', 'work_order_id', 'work_order_service_id'], 'invoice_items_service_foreign')->references(['workshop_id', 'work_order_id', 'id'])->on('work_order_services')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['workshop_id', 'work_order_id', 'work_order_spare_part_id'], 'invoice_items_part_foreign')->references(['workshop_id', 'work_order_id', 'id'])->on('work_order_spare_parts')->restrictOnDelete()->restrictOnUpdate();
            $table->unique(['invoice_id', 'work_order_service_id']);
            $table->unique(['invoice_id', 'work_order_spare_part_id']);
            $table->index(['invoice_id', 'id']);
        });
        DB::statement("ALTER TABLE invoice_items ADD CONSTRAINT invoice_items_valid CHECK (quantity > 0 AND unit_price >= 0 AND subtotal::numeric = quantity::numeric * unit_price::numeric
            AND ((item_type = 'service' AND work_order_service_id IS NOT NULL AND work_order_spare_part_id IS NULL)
            OR (item_type = 'spare_part' AND work_order_service_id IS NULL AND work_order_spare_part_id IS NOT NULL)))");
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('invoice_id');
            $table->bigInteger('amount');
            $table->string('method', 20);
            $table->string('reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('paid_at');
            $table->unique(['workshop_id', 'id', 'invoice_id'], 'payments_reversal_reference');
            $table->foreign(['workshop_id', 'invoice_id'])->references(['workshop_id', 'id'])->on('invoices')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['invoice_id', 'id']);
        });
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_valid CHECK (amount > 0 AND method IN ('cash','bank_transfer','other') AND (method <> 'other' OR (note IS NOT NULL AND length(trim(note)) > 0)))");
        Schema::create('payment_reversals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('payment_id')->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestampTz('created_at');
            $table->foreign(['workshop_id', 'payment_id', 'invoice_id'], 'payment_reversals_source_foreign')->references(['workshop_id', 'id', 'invoice_id'])->on('payments')->restrictOnDelete()->restrictOnUpdate();
        });
        DB::statement('ALTER TABLE payment_reversals ADD CONSTRAINT reversal_reason CHECK (length(trim(reason)) > 0)');
        Schema::create('invoice_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->unsignedBigInteger('invoice_id');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->uuid('operation_key');
            $table->string('request_fingerprint', 64);
            $table->jsonb('details');
            $table->timestampTz('created_at');
            $table->unique(['workshop_id', 'operation_key']);
            $table->foreign(['workshop_id', 'invoice_id'])->references(['workshop_id', 'id'])->on('invoices')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['invoice_id', 'id']);
        });
        DB::unprepared("CREATE OR REPLACE FUNCTION protect_billing_ledger() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Billing history is append-only'; END; $$");
        foreach (['payments', 'payment_reversals', 'invoice_events'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION protect_billing_ledger()");
        }
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION protect_invoice_document() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF TG_OP = 'DELETE' THEN RAISE EXCEPTION 'Invoice deletion is forbidden'; END IF;
 IF OLD.status = 'void' THEN RAISE EXCEPTION 'Voided invoices are immutable'; END IF;
 IF NEW.workshop_id <> OLD.workshop_id OR NEW.work_order_id <> OLD.work_order_id OR NEW.id <> OLD.id THEN RAISE EXCEPTION 'Invoice ownership is immutable'; END IF;
 IF OLD.status = 'issued' AND
 (to_jsonb(NEW) - ARRAY['status','version','voided_at','voided_by','void_reason','updated_at']) IS DISTINCT FROM
 (to_jsonb(OLD) - ARRAY['status','version','voided_at','voided_by','void_reason','updated_at']) THEN
 RAISE EXCEPTION 'Issued financial snapshots are immutable'; END IF;
 IF OLD.status = 'issued' AND NEW.status NOT IN ('issued','void') THEN RAISE EXCEPTION 'Invalid invoice transition'; END IF;
 IF NEW.status = 'issued' AND OLD.status = 'draft' THEN
  IF NOT EXISTS (SELECT 1 FROM work_orders WHERE id = NEW.work_order_id AND workshop_id = NEW.workshop_id AND status = 'completed') THEN RAISE EXCEPTION 'Only completed work can be invoiced'; END IF;
  IF NOT EXISTS (SELECT 1 FROM invoice_items WHERE invoice_id = NEW.id) OR (SELECT COALESCE(SUM(subtotal),0) FROM invoice_items WHERE invoice_id = NEW.id) <> NEW.total THEN RAISE EXCEPTION 'Invoice total must match its items'; END IF;
 END IF;
 IF NEW.status = 'void' AND EXISTS (SELECT 1 FROM payments p WHERE p.invoice_id = NEW.id AND NOT EXISTS (SELECT 1 FROM payment_reversals r WHERE r.payment_id = p.id)) THEN RAISE EXCEPTION 'Paid invoices cannot be voided'; END IF;
 RETURN NEW;
END; $$;
CREATE TRIGGER invoices_protected BEFORE UPDATE OR DELETE ON invoices FOR EACH ROW EXECUTE FUNCTION protect_invoice_document();
CREATE OR REPLACE FUNCTION protect_invoice_item() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE parent_status text;
BEGIN
 SELECT status INTO parent_status FROM invoices WHERE id = CASE WHEN TG_OP = 'DELETE' THEN OLD.invoice_id ELSE NEW.invoice_id END FOR KEY SHARE;
 IF parent_status <> 'draft' THEN RAISE EXCEPTION 'Final invoice items are immutable'; END IF;
 IF TG_OP = 'UPDATE' AND (NEW.invoice_id <> OLD.invoice_id OR NEW.workshop_id <> OLD.workshop_id OR NEW.work_order_id <> OLD.work_order_id) THEN RAISE EXCEPTION 'Invoice item ownership is immutable'; END IF;
 IF TG_OP = 'DELETE' THEN RETURN OLD; END IF; RETURN NEW;
END; $$;
CREATE TRIGGER invoice_items_protected BEFORE INSERT OR UPDATE OR DELETE ON invoice_items FOR EACH ROW EXECUTE FUNCTION protect_invoice_item();
CREATE OR REPLACE FUNCTION validate_billing_entry() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE document invoices%ROWTYPE; already_paid numeric;
BEGIN
 SELECT * INTO document FROM invoices WHERE id = NEW.invoice_id AND workshop_id = NEW.workshop_id FOR UPDATE;
 IF NOT FOUND OR document.status <> 'issued' THEN RAISE EXCEPTION 'Payments and corrections require an issued invoice'; END IF;
 IF TG_TABLE_NAME = 'payments' THEN
  SELECT COALESCE(SUM(p.amount),0) INTO already_paid FROM payments p WHERE p.invoice_id = document.id AND NOT EXISTS (SELECT 1 FROM payment_reversals r WHERE r.payment_id = p.id);
  IF already_paid + NEW.amount > document.total THEN RAISE EXCEPTION 'Payment exceeds outstanding balance'; END IF;
 END IF;
 RETURN NEW;
END; $$;
CREATE TRIGGER payments_balance_guard BEFORE INSERT ON payments FOR EACH ROW EXECUTE FUNCTION validate_billing_entry();
CREATE TRIGGER reversals_state_guard BEFORE INSERT ON payment_reversals FOR EACH ROW EXECUTE FUNCTION validate_billing_entry();
SQL);
    }

    public function down(): void
    {
        foreach (['invoice_events', 'payment_reversals', 'payments', 'invoice_items', 'invoices'] as $name) {
            Schema::dropIfExists($name);
        }
        DB::statement('DROP FUNCTION IF EXISTS protect_billing_ledger()');
        DB::statement('DROP FUNCTION IF EXISTS protect_invoice_item()');
        DB::statement('DROP FUNCTION IF EXISTS protect_invoice_document()');
        DB::statement('DROP FUNCTION IF EXISTS validate_billing_entry()');
        foreach (['work_order_services', 'work_order_spare_parts'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropUnique($name.'_invoice_source'));
        }
        Schema::table('workshops', fn (Blueprint $table) => $table->dropColumn('next_invoice_number'));
    }
};
