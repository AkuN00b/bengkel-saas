<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_has_workshop_relationships_optional_details_and_bigint_identity(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = $workshop->customers()->create(['name' => 'Customer'])->fresh();
        $this->assertIsInt($customer->id);
        $this->assertTrue($customer->workshop->is($workshop));
        $this->assertTrue($workshop->customers()->sole()->is($customer));
        $this->assertNull($customer->phone);
        $this->assertNull($customer->notes);
        $this->assertNotNull($customer->created_at);
        $this->assertNotNull($customer->updated_at);
    }

    public function test_customer_workshop_and_id_are_not_mass_assignable(): void
    {
        $customer = Customer::factory()->create();
        $other = Workshop::factory()->create();
        $id = $customer->id;
        $workshopId = $customer->workshop_id;
        $customer->fill(['id' => 9999999, 'workshop_id' => $other->id, 'name' => 'Allowed'])->save();
        $this->assertSame($id, $customer->fresh()->id);
        $this->assertSame($workshopId, $customer->fresh()->workshop_id);
        $this->assertSame('Allowed', $customer->fresh()->name);
    }

    #[DataProvider('invalidDatabaseRows')]
    public function test_database_enforces_required_name_and_existing_workshop(array $overrides): void
    {
        $workshop = Workshop::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('customers')->insert(array_replace(['workshop_id' => $workshop->id, 'name' => 'Valid'], $overrides));
    }

    public static function invalidDatabaseRows(): array
    {
        return [[['name' => null]], [['name' => '']], [['name' => '   ']], [['workshop_id' => null]], [['workshop_id' => 999999999]]];
    }

    public function test_workshop_deletion_is_restricted_while_customers_exist(): void
    {
        $customer = Customer::factory()->create();
        $this->expectException(QueryException::class);
        $customer->workshop->delete();
    }

    public function test_customer_migration_rolls_back_and_reapplies_without_touching_workshops_or_users(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $vehicleMigration = require database_path('migrations/2026_11_10_000000_create_vehicles_table.php');
        $vehicleMigration->down();
        $migration = require database_path('migrations/2026_11_09_000000_create_customers_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('customers'));
        $this->assertDatabaseHas('workshops', ['id' => $customer->workshop_id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $migration->up();
        $vehicleMigration->up();
        $this->assertTrue(Schema::hasTable('customers'));
        $this->assertTrue(Customer::factory()->create(['workshop_id' => $customer->workshop_id])->exists);
    }
}
