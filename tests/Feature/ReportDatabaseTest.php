<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class ReportDatabaseTest extends TestCase
{
    use BillingFixtures, DatabaseMigrations;

    public function test_index_round_trip_preserves_operational_records_and_supports_tenant_time_scans(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 20000, 'method' => 'cash']))->assertRedirect();
        $tables = ['users', 'workshops', 'workshop_user', 'customers', 'vehicles', 'work_orders', 'work_order_events', 'services', 'spare_parts', 'work_order_services', 'work_order_spare_parts', 'inventory_movements', 'invoices', 'invoice_items', 'payments', 'payment_reversals', 'invoice_events'];
        $read = fn () => collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->orderBy('id')->get()->toJson()])->all();
        $before = $read();
        $migration = require database_path('migrations/2026_11_11_010000_add_reporting_indexes.php');
        $migration->down();
        $this->assertSame($before, $read());
        $migration->up();
        $this->assertSame($before, $read());
        // Tiny fixtures naturally favour sequential scans. Disabling them here verifies index eligibility,
        // not a claim that the planner should prefer an index on empty production tables.
        $scans = [
            'payments_report_time' => ['SELECT id FROM payments WHERE workshop_id=? AND paid_at>=? ORDER BY paid_at,id', [$owner->workshop_id, '2026-01-01']],
            'reversals_report_time' => ['SELECT id FROM payment_reversals WHERE workshop_id=? AND created_at>=? ORDER BY created_at,id', [$owner->workshop_id, '2026-01-01']],
            'invoices_report_issued' => ['SELECT id FROM invoices WHERE workshop_id=? AND issued_at>=? ORDER BY issued_at,id', [$owner->workshop_id, '2026-01-01']],
            'invoices_report_voided' => ['SELECT id FROM invoices WHERE workshop_id=? AND issued_at IS NOT NULL AND voided_at>=? ORDER BY voided_at,id', [$owner->workshop_id, '2026-01-01']],
            'work_orders_vehicle_history' => ['SELECT id FROM work_orders WHERE workshop_id=? AND vehicle_id=? ORDER BY id', [$owner->workshop_id, $invoice->order->vehicle_id]],
        ];
        DB::transaction(function () use ($scans): void {
            DB::statement('SET LOCAL enable_seqscan = off');
            DB::statement('SET LOCAL enable_sort = off');
            foreach ($scans as $index => [$sql,$bindings]) {
                $plan = DB::select('EXPLAIN (FORMAT JSON) '.$sql, $bindings);
                $this->assertStringContainsString($index, json_encode($plan));
            }
        });
    }
}
