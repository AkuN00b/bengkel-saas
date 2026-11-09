<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeding_does_not_create_accounts_or_tenant_data(): void
    {
        $plans = Plan::orderBy('id')->get()->toArray();

        $this->seed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('workshops', 0);
        $this->assertDatabaseCount('workshop_user', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertSame($plans, Plan::orderBy('id')->get()->toArray());
    }

    public function test_default_seeding_can_be_repeated_without_changing_existing_accounts(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com'])->fresh();
        $attributes = $user->getAttributes();

        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, $user->fresh()->getAttributes());
    }
}
