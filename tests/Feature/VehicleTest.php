<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_normalizes_plates_and_supports_optional_customers(): void
    {
        $workshop = Workshop::factory()->create();
        $vehicle = $workshop->vehicles()->create(['plate_number' => ' b   1234 abc '])->fresh();
        $this->assertIsInt($vehicle->id);
        $this->assertSame('B 1234 ABC', $vehicle->plate_number);
        $this->assertSame('B1234ABC', $vehicle->normalized_plate_number);
        $this->assertNull($vehicle->customer_id);
        $this->assertTrue($vehicle->workshop->is($workshop));
        $this->assertTrue($workshop->vehicles()->sole()->is($vehicle));

        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $vehicle->update(['customer_id' => $customer->id, 'year' => '2020']);
        $this->assertTrue($vehicle->fresh()->customer->is($customer));
        $this->assertTrue($customer->vehicles()->sole()->is($vehicle));
        $this->assertSame(2020, $vehicle->fresh()->year);
        $vehicle->update(['customer_id' => null]);
        $this->assertNull($vehicle->fresh()->customer);
    }

    public function test_workshop_identity_and_normalized_plate_are_not_mass_assignable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $id = $vehicle->id;
        $workshopId = $vehicle->workshop_id;
        $vehicle->fill(['id' => 999999, 'workshop_id' => Workshop::factory()->create()->id,
            'normalized_plate_number' => 'FORGED', 'plate_number' => 'ab 001 cd'])->save();
        $this->assertSame($id, $vehicle->fresh()->id);
        $this->assertSame($workshopId, $vehicle->fresh()->workshop_id);
        $this->assertSame('AB001CD', $vehicle->fresh()->normalized_plate_number);
    }

    public function test_the_same_normalized_plate_is_allowed_in_different_workshops(): void
    {
        $first = Vehicle::factory()->create(['plate_number' => 'B 1234 AB']);
        $second = Vehicle::factory()->create(['plate_number' => 'b1234ab']);
        $this->assertSame($first->normalized_plate_number, $second->normalized_plate_number);
        $this->assertNotSame($first->workshop_id, $second->workshop_id);
    }

    public function test_duplicate_normalized_plates_in_one_workshop_are_rejected_by_the_database(): void
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'B 1234 AB']);
        $this->expectException(QueryException::class);
        Vehicle::factory()->create(['workshop_id' => $vehicle->workshop_id, 'plate_number' => 'b1234ab']);
    }

    #[DataProvider('invalidRows')]
    public function test_database_constraints_reject_invalid_vehicle_records(array $overrides): void
    {
        $workshop = Workshop::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('vehicles')->insert(array_replace([
            'workshop_id' => $workshop->id, 'plate_number' => 'B 1234 AB', 'normalized_plate_number' => 'B1234AB',
        ], $overrides));
    }

    public static function invalidRows(): array
    {
        return [
            [['plate_number' => null]], [['plate_number' => '']], [['normalized_plate_number' => null]],
            [['plate_number' => 'b 1234 ab']], [['plate_number' => 'B-1234-AB']],
            [['normalized_plate_number' => 'FORGED']], [['workshop_id' => null]],
            [['workshop_id' => 999999999]], [['customer_id' => 999999999]],
            [['year' => 1899]], [['year' => 2101]],
        ];
    }

    public function test_database_rejects_cross_workshop_customer_association_even_without_http_validation(): void
    {
        $vehicle = Vehicle::factory()->create();
        $customer = Customer::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('vehicles')->where('id', $vehicle->id)->update(['customer_id' => $customer->id]);
    }

    public function test_referenced_customer_cannot_be_deleted_and_existing_vehicle_is_preserved(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create(['workshop_id' => $customer->workshop_id, 'customer_id' => $customer->id]);
        try {
            DB::transaction(fn () => $customer->delete());
            $this->fail('A referenced customer must not be deleted.');
        } catch (QueryException $exception) {
            $this->assertContains((string) $exception->getCode(), ['23001', '23503']);
        }
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'customer_id' => $customer->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_vehicle_migration_rollback_preserves_existing_customers_and_workshops(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['workshop_id' => $customer->workshop_id, 'customer_id' => $customer->id]);
        $orders = require database_path('migrations/2026_11_10_110000_create_work_orders.php');
        $inventory = require database_path('migrations/2026_11_10_120000_create_services_and_inventory.php');
        $inventory->down();
        $orders->down();
        $migration = require database_path('migrations/2026_11_10_000000_create_vehicles_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('vehicles'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('workshops', ['id' => $customer->workshop_id]);
        $migration->up();
        $orders->up();
        $inventory->up();
        $this->assertTrue(Schema::hasTable('vehicles'));
        $this->assertTrue(Vehicle::factory()->create(['workshop_id' => $customer->workshop_id])->exists);
    }
}
