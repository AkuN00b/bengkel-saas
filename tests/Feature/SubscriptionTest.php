<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function subscription(array $overrides = []): Subscription
    {
        return Subscription::create(array_replace([
            'workshop_id' => Workshop::factory()->create()->id,
            'plan_id' => Plan::where('code', 'trial')->sole()->id,
            'status' => SubscriptionStatus::Trialing,
            'starts_at' => now(), 'trial_ends_at' => now()->addDays(14), 'ends_at' => now()->addDays(14),
        ], $overrides));
    }

    public function test_catalog_enables_only_free_trial_and_leaves_paid_pricing_unpublished(): void
    {
        $this->assertDatabaseCount('plans', 3);
        $trial = Plan::where('code', 'trial')->sole();
        $this->assertTrue($trial->is_active);
        $this->assertSame(0, $trial->price_monthly);
        foreach (['starter', 'pro'] as $code) {
            $plan = Plan::where('code', $code)->sole();
            $this->assertFalse($plan->is_active);
            $this->assertNull($plan->price_monthly);
        }
    }

    public function test_trial_expires_at_exact_boundary_without_mutating_persisted_status(): void
    {
        $this->freezeTime();
        $subscription = $this->subscription();
        $this->assertTrue($subscription->isTrialing());
        $this->travel(14)->days();
        $this->assertFalse($subscription->fresh()->isTrialing());
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->fresh()->status);
    }

    public function test_future_and_cancelled_trials_are_not_current_trials(): void
    {
        $future = $this->subscription(['starts_at' => now()->addDay()]);
        $this->assertFalse($future->isTrialing());
        $cancelled = $this->subscription(['status' => SubscriptionStatus::Cancelled]);
        $this->assertFalse($cancelled->isTrialing());
    }

    #[DataProvider('invalidSubscriptionFields')]
    public function test_database_rejects_invalid_subscription_data(array $overrides): void
    {
        $values = [
            'workshop_id' => Workshop::factory()->create()->id,
            'plan_id' => Plan::where('code', 'trial')->sole()->id,
            'status' => 'trialing', 'starts_at' => now(), 'trial_ends_at' => now()->addDays(14),
        ];
        $this->expectException(QueryException::class);
        DB::table('subscriptions')->insert(array_replace($values, $overrides));
    }

    public static function invalidSubscriptionFields(): array
    {
        return [
            [['status' => 'unknown']], [['trial_ends_at' => null]],
            [['trial_ends_at' => '2000-01-01']], [['ends_at' => '2000-01-01']],
            [['workshop_id' => 999999999]], [['plan_id' => 999999999]],
        ];
    }

    public function test_database_prevents_two_current_subscriptions_for_one_workshop(): void
    {
        $first = $this->subscription();
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23505');
        $this->subscription(['workshop_id' => $first->workshop_id, 'status' => SubscriptionStatus::Active]);
    }

    public function test_expired_history_can_coexist_with_one_new_subscription(): void
    {
        $history = $this->subscription(['status' => SubscriptionStatus::Expired]);
        $this->subscription(['workshop_id' => $history->workshop_id]);
        $this->assertSame(2, $history->workshop->subscriptions()->count());
    }

    #[DataProvider('invalidPlanFields')]
    public function test_database_rejects_invalid_plan_configuration(array $values): void
    {
        $this->expectException(QueryException::class);
        DB::table('plans')->where('code', 'trial')->update($values);
    }

    public static function invalidPlanFields(): array
    {
        return [[['price_monthly' => -1]], [['user_limit' => 0]], [['price_monthly' => null]]];
    }

    public function test_subscriptions_prevent_deleting_a_referenced_plan(): void
    {
        $subscription = $this->subscription();
        $this->expectException(QueryException::class);
        $subscription->plan->delete();
    }

    public function test_subscriptions_prevent_deleting_a_referenced_workshop(): void
    {
        $subscription = $this->subscription();
        $this->expectException(QueryException::class);
        $subscription->workshop->delete();
    }

    public function test_new_schema_can_be_reversed_and_reapplied(): void
    {
        $workshop = Workshop::factory()->create();
        $subscriptions = require database_path('migrations/2026_10_31_000004_create_subscriptions_table.php');
        $plans = require database_path('migrations/2026_10_31_000003_create_plans_table.php');
        $subscriptions->down();
        $plans->down();
        $this->assertFalse(Schema::hasTable('plans'));
        $this->assertDatabaseHas('workshops', ['id' => $workshop->id]);
        $plans->up();
        $subscriptions->up();
        $this->assertDatabaseCount('plans', 3);
        $this->assertTrue($this->subscription()->exists);
    }
}
