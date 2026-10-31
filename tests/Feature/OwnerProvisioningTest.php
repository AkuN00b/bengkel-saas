<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\WorkshopRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class OwnerProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Owner Uji', 'email' => 'owner@example.com', 'workshop_name' => 'Bengkel Uji',
            'password' => 'owner-password-123', 'password_confirmation' => 'owner-password-123',
        ], $overrides);
    }

    public function test_registration_creates_complete_owner_trial_and_ignores_client_privileges(): void
    {
        $this->travelTo(now()->startOfSecond());
        Event::fake([Registered::class]);
        $other = Workshop::factory()->create();
        $this->post('/register', $this->data([
            'workshop_id' => $other->id, 'role' => 'super_admin', 'is_super_admin' => true,
            'plan_id' => Plan::where('code', 'pro')->sole()->id,
            'status' => 'active', 'trial_ends_at' => '2099-01-01', 'slug' => $other->slug,
        ]))->assertRedirect(route('dashboard'));

        $user = User::sole();
        $workshop = $user->workshops()->sole();
        $subscription = $workshop->subscriptions()->sole();
        $this->assertSame('Bengkel Uji', $workshop->name);
        $this->assertFalse($workshop->is($other));
        $this->assertTrue($user->hasWorkshopRole($workshop, WorkshopRole::Owner));
        $this->assertFalse($user->isMemberOf($other));
        $this->assertFalse($user->isSuperAdmin());
        $this->assertSame('trial', $subscription->plan->code);
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->status);
        $this->assertTrue($subscription->starts_at->equalTo(now()));
        $this->assertTrue($subscription->trial_ends_at->equalTo(now()->addDays(14)));
        $this->assertTrue($subscription->ends_at->equalTo($subscription->trial_ends_at));
        $this->assertTrue($subscription->isTrialing());
        $this->assertTrue($subscription->workshop->is($workshop));
        $this->assertTrue($subscription->plan->subscriptions()->sole()->is($subscription));
        $this->assertSame(0, $subscription->plan->price_monthly);
        $this->assertDatabaseCount('workshop_user', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(session()->has('active_workshop_id'));
        Event::assertDispatched(Registered::class, fn ($event) => $event->user->is($user));
    }

    #[DataProvider('failureStages')]
    public function test_any_failure_rolls_back_every_provisioned_record(string $model): void
    {
        Event::fake([Registered::class]);
        Event::listen('eloquent.created: '.$model, fn () => throw new RuntimeException('Simulated provisioning failure'));
        $this->post('/register', $this->data())->assertStatus(500);
        foreach (['users', 'workshops', 'workshop_user', 'subscriptions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertGuest();
        Event::assertNotDispatched(Registered::class);
    }

    public static function failureStages(): array
    {
        return [[User::class], [Workshop::class], [WorkshopMembership::class], [Subscription::class]];
    }

    public function test_unavailable_trial_rejects_registration_without_partial_records(): void
    {
        Plan::where('code', 'trial')->update(['is_active' => false]);
        $this->post('/register', $this->data())->assertSessionHasErrors('workshop_name');
        foreach (['users', 'workshops', 'workshop_user', 'subscriptions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertGuest();
    }

    public function test_workshops_with_the_same_name_get_distinct_tenants_and_slugs(): void
    {
        $this->post('/register', $this->data())->assertRedirect(route('dashboard'));
        $this->post('/logout');
        $this->post('/register', $this->data(['email' => 'second@example.com']))->assertRedirect(route('dashboard'));
        [$first, $second] = Workshop::orderBy('id')->get()->all();
        $this->assertSame($first->name, $second->name);
        $this->assertNotSame($first->slug, $second->slug);
        $this->assertNotSame($first->users()->sole()->id, $second->users()->sole()->id);
        $this->assertDatabaseCount('subscriptions', 2);
    }

    public function test_validation_failure_does_not_provision_anything(): void
    {
        $this->post('/register', $this->data(['workshop_name' => '']))->assertSessionHasErrors('workshop_name');
        foreach (['users', 'workshops', 'workshop_user', 'subscriptions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
