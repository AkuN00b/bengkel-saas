<?php

namespace Tests\Feature;

use App\Actions\Inventory;
use App\Actions\WorkOrderItems;
use App\Actions\WorkOrders;
use App\Http\Controllers\ReportController;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderSparePart;
use App\Models\WorkshopMembership;
use App\Support\ReportSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use BillingFixtures, RefreshDatabase;

    public function test_dashboard_rechecks_membership_after_resolving_workshop_before_aggregating(): void
    {
        $owner = $this->owner();
        $this->mock(ReportSnapshot::class, function ($mock) use ($owner): void {
            $mock->shouldReceive('run')->once()->andReturnUsing(function ($read) use ($owner) {
                DB::table('workshop_user')->where('id', $owner->id)->update(['is_active' => false]);

                return $read();
            });
        });
        $this->getJson('/dashboard')->assertForbidden();
    }

    private function clock(string $time): void
    {
        $this->travelTo(CarbonImmutable::parse($time, 'Asia/Jakarta'));
    }

    private function at(string $time): array
    {
        return ['mode' => 'at', 'as_of' => '2026-11-10T'.$time];
    }

    private function lifecycle(): array
    {
        $this->clock('2026-11-10 09:00:00');
        $owner = $this->owner();
        $invoice = $this->issued($owner, 500000);
        $this->clock('2026-11-10 10:00:00');
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 200000, 'method' => 'cash']))->assertRedirect();
        $payment = Payment::where('invoice_id', $invoice->id)->sole();
        $this->clock('2026-11-10 11:00:00');
        $this->post('/invoices/'.$invoice->id.'/payments/'.$payment->id.'/reverse', $this->billData($invoice, ['reason' => 'Koreksi jam sebelas']))->assertRedirect();
        $this->clock('2026-11-10 12:00:00');
        $this->post('/invoices/'.$invoice->id.'/void', $this->billData($invoice, ['reason' => 'Batal jam dua belas']))->assertRedirect();
        $this->clock('2026-11-11 13:00:00');

        return [$owner, $invoice];
    }

    public function test_historical_positions_use_events_at_cutoff_even_when_invoice_is_now_void(): void
    {
        [$owner,$invoice] = $this->lifecycle();
        foreach (['08:59:59' => '0', '09:00:00' => '500000', '09:30:00' => '500000', '10:00:00' => '300000', '10:30:00' => '300000', '10:59:59' => '300000', '11:00:00' => '500000', '11:30:00' => '500000', '12:00:00' => '0', '12:30:00' => '0'] as $time => $remaining) {
            $this->get('/reports/receivables?'.http_build_query($this->at($time)))->assertOk()->assertViewHas('totals', fn ($totals) => $totals->remaining === $remaining);
        }
        $this->get('/reports/receivables/'.$invoice->id.'?'.http_build_query($this->at('10:30:00')))->assertOk()->assertSee('300.000')->assertDontSee('Koreksi jam sebelas')->assertDontSee('Batal jam dua belas')
            ->assertViewHas('events', fn ($events) => $events->total() === 2)->assertViewHas('position', fn ($p) => $p->document_state === 'issued');
        $this->get('/reports/receivables/'.$invoice->id.'?'.http_build_query($this->at('12:30:00')))->assertOk()->assertViewHas('position', fn ($p) => $p->document_state === 'void' && (string) $p->remaining === '0');
        $this->get('/reports/receivables/'.$invoice->id.'?'.http_build_query($this->at('08:59:59')))->assertOk()->assertSee('belum diterbitkan')->assertDontSee($invoice->number);
    }

    public function test_corrections_belong_to_correction_day_not_original_day_and_may_be_negative(): void
    {
        $this->clock('2026-11-09 23:59:59');
        $owner = $this->owner();
        $invoice = $this->issued($owner, 500000);
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 200000, 'method' => 'bank_transfer']))->assertRedirect();
        $payment = Payment::sole();
        $this->clock('2026-11-10 00:00:00');
        $this->post('/invoices/'.$invoice->id.'/payments/'.$payment->id.'/reverse', $this->billData($invoice, ['reason' => 'Salah catat']))->assertRedirect();
        $this->post('/invoices/'.$invoice->id.'/void', $this->billData($invoice, ['reason' => 'Ganti invoice']))->assertRedirect();
        $this->clock('2026-11-11 15:00:00');
        foreach (['payments' => ['200000', '-200000'], 'invoice-values' => ['500000', '-500000']] as $report => $values) {
            foreach (['2026-11-09', '2026-11-10'] as $index => $date) {
                $this->get('/reports/'.$report.'?preset=custom&from='.$date.'&to='.$date)->assertOk()->assertViewHas('totals', fn ($t) => $t->net === $values[$index]);
            }
        }
        $this->get('/reports/payments?method=cash')->assertOk()->assertViewHas('totals', fn ($t) => $t->received === '0');
        $this->get('/reports/payments?method=bank_transfer')->assertOk()->assertViewHas('totals', fn ($t) => $t->received === '200000' && $t->correction === '200000');
        $this->get('/reports/invoice-values')->assertOk()->assertViewHas('totals', fn ($t) => $t->service_gross === '500000' && $t->service_correction === '500000' && $t->part_gross === '0');
    }

    public function test_invoice_replacement_zero_total_and_void_drafts_preserve_official_trace(): void
    {
        $this->clock('2026-11-10 09:00:00');
        $owner = $this->owner();
        $invoice = $this->issued($owner, 0);
        $this->get('/reports/receivables')->assertViewHas('totals', fn ($t) => $t->count === 0);
        $this->post('/invoices/'.$invoice->id.'/void', $this->billData($invoice, ['reason' => 'V1']))->assertRedirect();
        $replacement = $this->draft($owner, $invoice->order);
        $this->post('/invoices/'.$replacement->id.'/issue', $this->billData($replacement))->assertRedirect();
        $draft = $this->draft($owner, $this->readyOrder($owner));
        $this->post('/invoices/'.$draft->id.'/void', $this->billData($draft, ['reason' => 'Draft salah']))->assertRedirect();
        $this->get('/reports/invoice-values')->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 3)->assertViewHas('totals', fn ($t) => $t->net === '0');
        $this->get('/reports/receivables/'.$draft->id)->assertNotFound();
    }

    public function test_owner_dashboard_and_outstanding_filter_include_partial_and_exclude_settled(): void
    {
        $this->clock('2026-11-10 15:00:00');
        $owner = $this->owner();
        $a = $this->issued($owner, 500000);
        $b = $this->issued($owner, 100000);
        $zero = $this->issued($owner, 0);
        $this->post('/invoices/'.$a->id.'/pay', $this->billData($a, ['amount' => 200000, 'method' => 'cash']))->assertRedirect();
        $this->part($owner);
        $this->part($owner, ['is_active' => false]);
        $this->part($owner, ['minimum_stock' => null]);
        $response = $this->get('/dashboard')->assertOk()->assertSee('Total sisa tagihan saat ini')->assertSee('400.000');
        $d = $response->viewData('operations');
        $this->assertSame('400000', $d['receivables']->remaining);
        $this->assertSame(2, $d['outstandingCount']);
        $this->assertSame(1, $d['lowCount']);
        $this->assertCount(7, $d['trend']);
        $this->get('/invoices?payment=outstanding')->assertOk()->assertViewHas('invoices', fn ($rows) => $rows->count() === 2 && ! $rows->contains('id', $zero->id));
    }

    public static function roles(): array
    {
        return [['cashier'], ['mechanic']];
    }

    #[DataProvider('roles')]
    public function test_financial_reports_exports_and_print_are_owner_only(string $role): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $staff = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => $role]);
        $staff->user->forceFill(['is_super_admin' => true])->save();
        $this->actingAs($staff->user);
        foreach (['payments', 'invoice-values', 'receivables'] as $report) {
            foreach (['', '/export', '/print'] as $suffix) {
                $this->get('/reports/'.$report.$suffix)->assertForbidden();
            }
        }
        $this->get('/reports/receivables/'.$invoice->id)->assertForbidden();
        $this->get('/reports/receivables/'.$invoice->id.'/print')->assertForbidden();
        $this->get('/service-history')->assertOk();
        $this->get('/service-history/export')->assertForbidden();
        $this->get('/service-history/print')->assertForbidden();
        $response = $this->get('/dashboard')->assertOk()->assertDontSee('Penerimaan tercatat bersih')->assertDontSee('Total sisa tagihan')->assertDontSee('Nilai invoice terbit');
        $this->assertArrayNotHasKey('payments', $response->viewData('operations'));
        if ($role === 'mechanic') {
            $response->assertDontSee('Stok menipis')->assertDontSee('Invoice belum lunas');
            $this->assertArrayNotHasKey('lowCount', $response->viewData('operations'));
        }
    }

    public function test_mechanic_queries_are_scoped_before_counts_and_pagination(): void
    {
        $owner = $this->owner();
        $own = $this->order($owner, true);
        $other = $this->order($owner, true);
        $this->actingAs($own->mechanic);
        $response = $this->get('/dashboard')->assertOk()->assertSee($own->number)->assertDontSee($other->number);
        $this->assertSame(1, $response->viewData('operations')['counts']['in_progress']);
        $this->get('/service-history?status=all')->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 1)->assertSee($own->number)->assertDontSee($other->number);
        $this->get('/service-history?status=all&mechanic_id='.$other->assigned_mechanic_id)->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
    }

    public function test_tenant_tampering_stale_context_and_revoked_membership_are_rejected(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $foreign = WorkshopMembership::factory()->create(['role' => 'owner']);
        $this->actingAs($foreign->user)->withSession(['active_workshop_id' => $foreign->workshop_id]);
        $this->get('/reports/receivables/'.$invoice->id)->assertNotFound();
        $this->get('/reports/payments?q='.$invoice->number)->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
        $this->get('/reports/payments?workshop_context='.$owner->workshop_id)->assertStatus(409);
        foreach (['vehicle_id' => $invoice->order->vehicle_id, 'customer_id' => Customer::factory()->create(['workshop_id' => $owner->workshop_id])->id, 'mechanic_id' => $invoice->order->assigned_mechanic_id] as $field => $value) {
            $this->getJson('/service-history?'.$field.'='.$value)->assertUnprocessable();
        }
        $foreign->is_active = false;
        $foreign->save();
        foreach (['/dashboard', '/reports/payments', '/reports/payments/export', '/service-history'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
    }

    public static function invalidFilters(): array
    {
        return [['mode=at&as_of=2099-01-01T00:00:00'], ['mode=at&as_of=0000-01-01T10:00:00'], ['mode=at&as_of=0001-01-01T00:00:00'], ['mode=at&as_of=2026-02-30T10:00:00'], ['mode=end_day&cutoff_date=2099-01-01'], ['preset=custom&from=2025-01-01&to=2026-11-10'], ['preset=custom&from=2026-11-11&to=2026-11-01'], ['method=bitcoin'], ['page=0'], ['vehicle_id=999999999999999999999999'], ['status=forged'], ['preset=all'], ['mode=at&as_of[]=bad']];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_fail_before_queries(string $query): void
    {
        $this->clock('2026-11-11 13:00:00');
        $this->owner();
        $this->getJson('/reports/receivables?'.$query)->assertUnprocessable();
    }

    public function test_local_timezones_and_year_month_midnight_boundaries(): void
    {
        $this->clock('2026-01-01 00:30:00');
        $owner = $this->owner();
        $invoice = $this->issued($owner, 100000);
        $this->clock('2026-02-01 00:00:00');
        $this->get('/reports/invoice-values?preset=custom&from=2025-12-31&to=2025-12-31')->assertViewHas('totals', fn ($t) => $t->net === '0');
        $this->get('/reports/invoice-values?preset=custom&from=2026-01-01&to=2026-01-31')->assertViewHas('totals', fn ($t) => $t->net === '100000');
        $owner->workshop->update(['timezone' => 'Asia/Jayapura']);
        $this->get('/reports/receivables?mode=at&as_of=2026-01-01T02:29:59')->assertViewHas('totals', fn ($t) => $t->remaining === '0');
        $this->get('/reports/receivables?mode=at&as_of=2026-01-01T02:30:00')->assertViewHas('totals', fn ($t) => $t->remaining === '100000')->assertSee('Asia/Jayapura');
        $this->get('/reports/receivables?mode=end_day&cutoff_date=2026-01-01')->assertViewHas('totals', fn ($t) => $t->remaining === '100000');
    }

    public function test_history_uses_stable_vehicle_and_work_order_customer_snapshots(): void
    {
        $owner = $this->owner();
        $customer = Customer::factory()->create(['workshop_id' => $owner->workshop_id, 'name' => 'Nama historis']);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $owner->workshop_id, 'customer_id' => $customer->id, 'plate_number' => 'B 1111 OLD', 'normalized_plate_number' => 'B1111OLD']);
        $order = app(WorkOrders::class)->create($owner->workshop, $owner->user, ['vehicle_id' => $vehicle->id, 'customer_id' => $customer->id, 'complaint' => 'Keluhan historis']);
        $order = app(WorkOrders::class)->mutate($owner->workshop, $owner->user, $order->id, ['version' => $order->version, 'action' => 'cancel', 'cancellation_reason' => 'Pelanggan pulang'], 'transition');
        $new = Customer::factory()->create(['workshop_id' => $owner->workshop_id]);
        $vehicle->update(['customer_id' => $new->id, 'plate_number' => 'B 2222 NEW', 'normalized_plate_number' => 'B2222NEW']);
        $customer->update(['name' => 'Nama baru']);
        $this->get('/service-history?vehicle_id='.$vehicle->id.'&preset=all')->assertOk()->assertSee('B 1111 OLD')->assertSee('Nama historis')->assertDontSee('Nama baru')->assertSee('Keluhan historis');
        $this->get('/service-history?customer_id='.$customer->id.'&preset=all')->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
        $this->get('/service-history?customer_id='.$new->id.'&preset=all')->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
        $this->get('/service-history?date_basis=completed')->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
        $this->get('/service-history?q=B1111OLD')->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
    }

    public function test_csv_and_print_preserve_cutoff_and_exact_totals_beyond_php_integer(): void
    {
        $this->clock('2026-11-10 09:00:00');
        $owner = $this->owner();
        $first = $this->issued($owner, PHP_INT_MAX);
        $second = $this->issued($owner, PHP_INT_MAX);
        $this->clock('2026-11-11 12:00:00');
        $params = http_build_query($this->at('09:00:00'));
        $this->get('/reports/receivables?'.$params)->assertOk()->assertSee('18.446.744.073.709.551.614')->assertViewHas('totals', fn ($t) => $t->remaining === '18446744073709551614');
        $csv = $this->get('/reports/receivables/export?'.$params)->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('18446744073709551614', $csv);
        $this->assertStringContainsString('2026-11-10 09:00:00', $csv);
        $this->get('/reports/receivables/print?'.$params)->assertOk()->assertSee('18.446.744.073.709.551.614')->assertSee('10/11/2026 09:00:00')->assertSee('rincian halaman');
        $this->assertStringContainsString('18446744073709551614', $this->get('/reports/invoice-values/export')->assertOk()->streamedContent());
    }

    public static function formulas(): array
    {
        return [['=SUM(1,1)'], ['+cmd'], ['-cmd'], ['@SUM(A1)'], ["\t=cmd"], ["\ncmd"], ['  =cmd']];
    }

    #[DataProvider('formulas')]
    public function test_untrusted_csv_text_cannot_start_formulas(string $text): void
    {
        $this->assertSame("'".$text, ReportController::text($text));
    }

    public function test_pagination_literal_search_and_streaming_do_not_silently_truncate(): void
    {
        $owner = $this->owner();
        for ($i = 0; $i < 205; $i++) {
            $this->order($owner);
        }
        $this->get('/service-history?status=all')->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 205 && $rows->count() === 20);
        $this->get('/service-history?status=all&page=11')->assertViewHas('rows', fn ($rows) => $rows->count() === 5);
        $csv = $this->get('/service-history/export?status=all')->assertOk()->streamedContent();
        $this->assertSame(205, substr_count($csv, 'WO-'));
        $this->get('/service-history?status=all&q=%25')->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
    }

    public function test_part_returns_and_catalog_edits_do_not_recalculate_official_values_or_allocate_installments(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $order->id, false, null, ['version' => $order->fresh()->version, 'catalog_id' => $part->id, 'quantity' => 2, 'unit_price' => 40000]);
        $line = WorkOrderSparePart::sole();
        $inventory = app(Inventory::class);
        $usage = $inventory->move($owner->workshop, $owner->user, $part->id, ['version' => $order->fresh()->version, 'stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid()], 'work_order_usage', $order->id, $line->id);
        $this->finish($owner, $order);
        $invoice = $this->draft($owner, $order);
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertRedirect();
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 50000, 'method' => 'other', 'note' => '=SUM(A1)']))->assertRedirect();
        $inventory->move($owner->workshop, $owner->user, $part->id, $this->stockData($part, ['quantity' => 1, 'reason' => 'Retur setelah terbit']), 'work_order_return', $order->id, $line->id, $usage->id, true);
        $part->forceFill(['name' => 'New catalog', 'selling_price' => 1])->save();
        Service::where('workshop_id', $owner->workshop_id)->update(['default_price' => 1]);
        $this->get('/reports/invoice-values')->assertOk()->assertViewHas('totals', fn ($t) => $t->service_gross === '100000' && $t->part_gross === '80000' && $t->service_net === '100000' && $t->part_net === '80000' && $t->net === '180000');
        $this->get('/reports/receivables')->assertOk()->assertViewHas('totals', fn ($t) => $t->remaining === '130000');
        $this->get('/reports/payments')->assertOk()->assertViewHas('totals', fn ($t) => $t->net === '50000');
        $csv = $this->get('/reports/payments/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=SUM(A1)", $csv);
        $this->get('/reports/payments?q='.$order->number)->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
    }

    public function test_history_queries_remain_bounded_and_customer_detail_links_preserve_context(): void
    {
        $owner = $this->owner();
        for ($i = 0; $i < 5; $i++) {
            $this->order($owner, true);
        }
        DB::enableQueryLog();
        $this->get('/service-history?status=all')->assertOk();
        $five = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
        for ($i = 0; $i < 10; $i++) {
            $this->order($owner, true);
        }
        DB::enableQueryLog();
        $this->get('/service-history?status=all')->assertOk();
        $fifteen = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertLessThanOrEqual($five + 1, $fifteen);
        $customer = Customer::factory()->create(['workshop_id' => $owner->workshop_id]);
        $this->get('/customers/'.$customer->id)->assertOk()->assertSee('Riwayat servis pelanggan');
        $this->get('/reports/receivables/999999999999999999999999')->assertNotFound();
        $mechanicId = WorkOrder::where('workshop_id', $owner->workshop_id)->first()->assigned_mechanic_id;
        WorkshopMembership::where('user_id', $mechanicId)->where('workshop_id', $owner->workshop_id)->delete();
        $this->get('/service-history?status=all&mechanic_id='.$mechanicId)->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
        $owner->delete();
        $this->getJson('/service-history')->assertForbidden();
    }
}
