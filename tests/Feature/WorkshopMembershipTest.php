<?php

namespace Tests\Feature;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkshopMembershipTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('workshopRoles')]
    public function test_membership_persists_a_role_through_both_relationships(WorkshopRole $role): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $user->workshops()->attach($workshop, ['role' => $role]);

        $member = $workshop->users()->sole();
        $tenant = $user->workshops()->sole();
        $this->assertTrue($member->is($user));
        $this->assertTrue($tenant->is($workshop));
        $this->assertInstanceOf(WorkshopMembership::class, $member->pivot);
        $this->assertSame($role, $member->pivot->role);
        $this->assertSame($role, $tenant->pivot->role);
        $this->assertIsInt($member->pivot->id);
        $this->assertNotNull($member->pivot->created_at);
        $this->assertNotNull($member->pivot->updated_at);
        $this->assertTrue($user->isMemberOf($workshop));
        $this->assertTrue($user->hasWorkshopRole($workshop, $role));
    }

    public static function workshopRoles(): array
    {
        return [[WorkshopRole::Owner], [WorkshopRole::Cashier], [WorkshopRole::Mechanic]];
    }

    public function test_factory_creates_a_membership_with_resolvable_user_and_workshop(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $this->assertTrue($membership->user->exists);
        $this->assertTrue($membership->workshop->exists);
        $this->assertSame(WorkshopRole::Mechanic, $membership->fresh()->role);
        $this->assertTrue($membership->user->isMemberOf($membership->workshop));
    }

    public function test_roles_and_memberships_are_specific_to_each_workshop(): void
    {
        $user = User::factory()->create();
        [$first, $second, $unrelated] = Workshop::factory()->count(3)->create()->all();
        $first->users()->attach($user, ['role' => WorkshopRole::Owner]);
        $second->users()->attach($user, ['role' => WorkshopRole::Mechanic]);

        $this->assertTrue($user->hasWorkshopRole($first, WorkshopRole::Owner));
        $this->assertTrue($user->hasWorkshopRole($second, WorkshopRole::Mechanic));
        $this->assertFalse($user->hasWorkshopRole($second, WorkshopRole::Owner));
        $this->assertFalse($user->hasWorkshopRole($first, WorkshopRole::Cashier));
        $this->assertFalse($user->isMemberOf($unrelated));
        $this->assertFalse($user->hasWorkshopRole($unrelated, WorkshopRole::Owner));
        $this->assertFalse(User::factory()->create()->isMemberOf($first));
    }

    public function test_database_rejects_duplicate_membership_even_with_a_different_role(): void
    {
        $membership = WorkshopMembership::factory()->create(['role' => WorkshopRole::Owner]);
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23505');
        $membership->workshop->users()->attach($membership->user, ['role' => WorkshopRole::Cashier]);
    }

    #[DataProvider('invalidRoles')]
    public function test_database_rejects_invalid_or_missing_workshop_roles(mixed $role): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('workshop_user')->insert([
            'user_id' => $user->id, 'workshop_id' => $workshop->id, 'role' => $role,
        ]);
    }

    public static function invalidRoles(): array
    {
        return [['super_admin'], ['admin'], [''], [null]];
    }

    public function test_membership_cannot_be_created_without_explicit_role(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->expectException(QueryException::class);
        $user->workshops()->attach($workshop);
    }

    #[DataProvider('foreignKeyFields')]
    public function test_database_rejects_orphan_memberships(string $field): void
    {
        $data = WorkshopMembership::factory()->create()->getAttributes();
        unset($data['id']);
        $data[$field] = 999999999;
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23503');
        DB::table('workshop_user')->insert($data);
    }

    public static function foreignKeyFields(): array
    {
        return [['user_id'], ['workshop_id']];
    }

    public function test_checks_see_changed_and_revoked_roles_despite_a_loaded_relationship(): void
    {
        $membership = WorkshopMembership::factory()->create(['role' => WorkshopRole::Owner]);
        $user = $membership->user;
        $workshop = $membership->workshop;
        $user->load('workshops');

        $workshop->users()->updateExistingPivot($user->id, ['role' => WorkshopRole::Cashier]);
        $this->assertFalse($user->hasWorkshopRole($workshop, WorkshopRole::Owner));
        $this->assertTrue($user->hasWorkshopRole($workshop, WorkshopRole::Cashier));
        $this->assertSame(WorkshopRole::Cashier, $membership->fresh()->role);

        $workshop->users()->detach($user);
        $this->assertFalse($user->isMemberOf($workshop));
        $this->assertFalse($user->hasWorkshopRole($workshop, WorkshopRole::Cashier));
    }

    public function test_platform_admin_status_is_separate_from_workshop_roles(): void
    {
        $membership = WorkshopMembership::factory()->create(['role' => WorkshopRole::Owner]);
        $this->assertFalse($membership->user->isSuperAdmin());

        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();
        $this->assertTrue($admin->fresh()->isSuperAdmin());
        $this->assertFalse($admin->isMemberOf($membership->workshop));
        $this->assertFalse($admin->hasWorkshopRole($membership->workshop, WorkshopRole::Owner));
        $this->assertFalse(User::factory()->create()->isSuperAdmin());
    }

    public function test_platform_admin_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $user->fill(['is_super_admin' => true])->save();
        $this->assertFalse($user->fresh()->isSuperAdmin());
    }

    #[DataProvider('parentModels')]
    public function test_memberships_prevent_silent_parent_deletion(string $parent): void
    {
        $membership = WorkshopMembership::factory()->create();
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23503');
        $membership->{$parent}->delete();
    }

    public static function parentModels(): array
    {
        return [['user'], ['workshop']];
    }

    public function test_detaching_membership_preserves_the_user_and_workshop(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $user = $membership->user;
        $workshop = $membership->workshop;
        $user->workshops()->detach($workshop);

        $this->assertDatabaseCount('workshop_user', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('workshops', ['id' => $workshop->id]);
    }

    public function test_migrations_can_be_reversed_without_deleting_users_or_workshops(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $adminMigration = require database_path('migrations/2026_10_31_000002_add_is_super_admin_to_users_table.php');
        $pivotMigration = require database_path('migrations/2026_10_31_000001_create_workshop_user_table.php');
        $adminMigration->down();
        $pivotMigration->down();
        $this->assertDatabaseHas('users', ['id' => $membership->user_id]);
        $this->assertDatabaseHas('workshops', ['id' => $membership->workshop_id]);

        $pivotMigration->up();
        $adminMigration->up();
        $this->assertFalse(User::findOrFail($membership->user_id)->isSuperAdmin());
        $this->assertDatabaseCount('workshop_user', 0);
        $this->assertTrue(WorkshopMembership::factory()->create()->exists);
    }

    public function test_membership_has_no_http_assignment_endpoint(): void
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user)->post('/workshops/'.$workshop->id.'/members', [
            'user_id' => $user->id, 'role' => 'owner',
        ])->assertNotFound();
        $this->assertDatabaseCount('workshop_user', 0);
    }
}
