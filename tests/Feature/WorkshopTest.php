<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_can_be_persisted_with_optional_contact_details(): void
    {
        $workshop = Workshop::create([
            'name' => 'Bengkel Motor', 'slug' => 'bengkel-motor',
            'phone' => '081234567890', 'address' => 'Jalan Uji 1',
            'timezone' => 'Asia/Makassar',
        ])->fresh();

        $this->assertIsInt($workshop->id);
        $this->assertSame('081234567890', $workshop->phone);
        $this->assertSame('Jalan Uji 1', $workshop->address);
        $this->assertSame('Asia/Makassar', $workshop->timezone);
        $this->assertNotNull($workshop->created_at);
        $this->assertNotNull($workshop->updated_at);
    }

    public function test_minimal_workshop_uses_the_timezone_default_without_contact_details(): void
    {
        $workshop = Workshop::create(['name' => 'Bengkel Minimal', 'slug' => 'minimal'])->fresh();

        $this->assertSame('Asia/Jakarta', $workshop->timezone);
        $this->assertNull($workshop->phone);
        $this->assertNull($workshop->address);

        DB::table('workshops')->insert(['name' => 'SQL Workshop', 'slug' => 'sql-workshop']);
        $this->assertSame('Asia/Jakarta', Workshop::where('slug', 'sql-workshop')->sole()->timezone);
    }

    public function test_workshops_can_share_a_name_but_have_independent_records(): void
    {
        [$first, $second] = Workshop::factory()->count(2)->create(['name' => 'Bengkel Sama'])->all();
        $first->update(['phone' => '081234567890']);

        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($first->slug, $second->slug);
        $this->assertNull($second->fresh()->phone);
        $this->assertDatabaseCount('workshops', 2);
    }

    public function test_database_rejects_duplicate_slugs(): void
    {
        Workshop::factory()->create(['slug' => 'unique-workshop']);
        $this->expectException(QueryException::class);
        Workshop::factory()->create(['slug' => 'unique-workshop']);
    }

    #[DataProvider('requiredFields')]
    public function test_database_rejects_null_required_fields(string $field): void
    {
        $this->expectException(QueryException::class);
        Workshop::factory()->create([$field => null]);
    }

    public static function requiredFields(): array
    {
        return [['name'], ['slug'], ['timezone']];
    }

    public function test_relationships_use_membership_instead_of_permanent_user_ownership(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();

        $this->assertSame('workshop_user', $user->workshops()->getTable());
        $this->assertSame('workshop_user', $workshop->users()->getTable());
        $this->assertSame('user_id', $user->workshops()->getForeignPivotKeyName());
        $this->assertSame('workshop_id', $user->workshops()->getRelatedPivotKeyName());
        $this->assertSame('workshop_id', $workshop->users()->getForeignPivotKeyName());
        $this->assertSame('user_id', $workshop->users()->getRelatedPivotKeyName());
        $this->assertFalse(Schema::hasColumn('users', 'workshop_id'));
        $this->assertFalse(Schema::hasColumn('workshops', 'user_id'));
        $this->assertTrue(Schema::hasTable('workshop_user'));
    }

    public function test_workshops_have_no_http_management_endpoint_yet(): void
    {
        $workshop = Workshop::factory()->create();
        $this->actingAs(User::factory()->create())->get('/workshops/'.$workshop->id)->assertNotFound();
        $this->post('/workshops', ['name' => 'Unauthorized creation'])->assertNotFound();
        $this->assertDatabaseCount('workshops', 1);
    }

    public function test_migration_can_be_rolled_back_and_reapplied_without_touching_users(): void
    {
        $user = User::factory()->create();
        // Roll dependent migrations back before the workshop table.
        $membershipMigration = require database_path('migrations/2026_10_31_000001_create_workshop_user_table.php');
        $membershipMigration->down();
        $migration = require database_path('migrations/2026_10_31_000000_create_workshops_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('workshops'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $migration->up();
        $membershipMigration->up();
        $this->assertTrue(Schema::hasTable('workshops'));
        $this->assertTrue(Workshop::factory()->create()->exists);
    }
}
