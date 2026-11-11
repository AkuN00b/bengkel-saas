<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', fn (Blueprint $t) => $t->index(['workshop_id', 'paid_at', 'id'], 'payments_report_time'));
        Schema::table('payment_reversals', fn (Blueprint $t) => $t->index(['workshop_id', 'created_at', 'id'], 'reversals_report_time'));
        DB::statement('CREATE INDEX invoices_report_issued ON invoices (workshop_id, issued_at, id) WHERE issued_at IS NOT NULL');
        DB::statement('CREATE INDEX invoices_report_voided ON invoices (workshop_id, voided_at, id) WHERE issued_at IS NOT NULL AND voided_at IS NOT NULL');
        Schema::table('work_orders', fn (Blueprint $t) => $t->index(['workshop_id', 'vehicle_id', 'id'], 'work_orders_vehicle_history'));
    }

    public function down(): void
    {
        Schema::table('work_orders', fn (Blueprint $t) => $t->dropIndex('work_orders_vehicle_history'));
        DB::statement('DROP INDEX IF EXISTS invoices_report_voided');
        DB::statement('DROP INDEX IF EXISTS invoices_report_issued');
        Schema::table('payment_reversals', fn (Blueprint $t) => $t->dropIndex('reversals_report_time'));
        Schema::table('payments', fn (Blueprint $t) => $t->dropIndex('payments_report_time'));
    }
};
