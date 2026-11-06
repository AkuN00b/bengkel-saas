<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplicationShellTest extends TestCase
{
    use RefreshDatabase;

    private function trial(Workshop $workshop, array $overrides = []): Subscription
    {
        return $workshop->subscriptions()->create(array_replace([
            'plan_id' => Plan::where('code', 'trial')->sole()->id,
            'status' => SubscriptionStatus::Trialing,
            'starts_at' => now()->subDay(), 'trial_ends_at' => now()->addDays(13),
            'ends_at' => now()->addDays(13),
        ], $overrides));
    }

    public function test_shell_shows_real_trial_and_only_existing_navigation(): void
    {
        $this->freezeSecond();
        $membership = WorkshopMembership::factory()->create(['role' => 'owner']);
        $subscription = $this->trial($membership->workshop);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()
            ->assertSee('Ringkasan')->assertSee('Kesiapan akun')->assertSee('Menu pengguna')
            ->assertSee($membership->workshop->name)->assertSee('Pemilik')->assertSee('Trial aktif')
            ->assertViewHas('trialDays', 13)->assertViewHas('subscription', fn ($value) => $value->is($subscription))
            ->assertSee(route('workshops.select'), false)->assertSee(route('logout'), false)
            ->assertDontSee('href="#"', false)->assertDontSee('/customers')->assertDontSee('/invoices');
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->fresh()->status);
    }

    #[DataProvider('trialDeadlines')]
    public function test_trial_days_and_labels_respect_exact_expiry(int $seconds, string $label, int $days): void
    {
        $this->freezeSecond();
        $membership = WorkshopMembership::factory()->create();
        $this->trial($membership->workshop, ['trial_ends_at' => now()->addSeconds($seconds), 'ends_at' => null]);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()
            ->assertSee($label)->assertViewHas('trialDays', $days);
    }

    public static function trialDeadlines(): array
    {
        return [[60, 'Trial aktif', 1], [86400, 'Trial aktif', 1], [86401, 'Trial aktif', 2], [0, 'Trial berakhir', 0], [-60, 'Trial berakhir', 0]];
    }

    public function test_an_earlier_subscription_end_limits_displayed_trial_days(): void
    {
        $this->freezeSecond();
        $membership = WorkshopMembership::factory()->create();
        $this->trial($membership->workshop, ['ends_at' => now()->addDay()]);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()->assertViewHas('trialDays', 1);
    }

    #[DataProvider('subscriptionStates')]
    public function test_subscription_state_is_displayed_without_changing_access(string $status, int $startOffset, ?int $endOffset, string $label): void
    {
        $this->freezeSecond();
        $membership = WorkshopMembership::factory()->create();
        $this->trial($membership->workshop, [
            'status' => $status, 'starts_at' => now()->addDays($startOffset),
            'ends_at' => $endOffset === null ? null : now()->addDays($endOffset),
        ]);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()->assertSee($label);
    }

    public static function subscriptionStates(): array
    {
        return [
            ['trialing', 1, 13, 'Trial belum dimulai'],
            ['active', -2, null, 'Paket aktif'],
            ['active', 1, 13, 'Paket belum dimulai'],
            ['active', -2, -1, 'Masa paket berakhir'],
            ['expired', -2, -1, 'Kedaluwarsa'],
            ['cancelled', -2, null, 'Dibatalkan'],
        ];
    }

    public function test_missing_subscription_is_not_replaced_with_fake_trial(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()->assertSee('Belum ada paket')
            ->assertViewHas('trialDays', null)->assertDontSee('hari tersisa');
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_subscription_details_follow_selected_workshop_and_ignore_client_ids(): void
    {
        $this->freezeSecond();
        $membership = WorkshopMembership::factory()->create();
        $other = WorkshopMembership::factory()->create();
        $own = $this->trial($membership->workshop, ['trial_ends_at' => now()->addDays(2), 'ends_at' => null]);
        $foreignPlan = Plan::create(['code' => 'private', 'name' => 'PRIVATE TENANT PLAN', 'price_monthly' => 0]);
        $this->trial($other->workshop, ['plan_id' => $foreignPlan->id]);
        $this->actingAs($membership->user)->get('/dashboard?workshop_id='.$other->workshop_id.'&subscription_id=999')
            ->assertOk()->assertViewHas('trialDays', 2)->assertViewHas('subscription', fn ($value) => $value->is($own))
            ->assertDontSee('PRIVATE TENANT PLAN')->assertDontSee($other->workshop->name);

        $membership->user->workshops()->attach($other->workshop, ['role' => 'cashier']);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/workshops/active', ['workshop_id' => $other->workshop_id])->assertRedirect(route('dashboard'));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/dashboard')->assertOk()->assertSee('PRIVATE TENANT PLAN')->assertSee('Kasir')->assertViewHas('trialDays', 13);
    }

    public function test_latest_subscription_history_is_displayed_deterministically(): void
    {
        $this->freezeSecond();
        $membership = WorkshopMembership::factory()->create();
        $this->trial($membership->workshop, ['status' => 'expired', 'starts_at' => now()->subDays(20)]);
        $latest = $this->trial($membership->workshop, ['status' => 'cancelled']);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()
            ->assertViewHas('subscription', fn ($value) => $value->is($latest))->assertSee('Dibatalkan');
    }

    public function test_display_date_uses_workshop_timezone_and_handles_invalid_timezone(): void
    {
        $this->travelTo(now()->setDateTime(2026, 11, 6, 0, 0, 0));
        $membership = WorkshopMembership::factory()->create();
        $this->trial($membership->workshop, ['trial_ends_at' => now()->addDay(), 'ends_at' => null]);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()->assertSee('07/11/2026 07:00');
        $membership->workshop->update(['timezone' => 'invalid-zone']);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/dashboard')->assertOk()->assertViewHas('displayTimezone', 'UTC')->assertSee('07/11/2026 00:00');
    }

    public function test_shell_escapes_plan_workshop_and_user_names(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $membership->user->update(['name' => '<script>user()</script>']);
        $membership->workshop->update(['name' => '<script>workshop()</script>']);
        Plan::where('code', 'trial')->update(['name' => '<script>plan()</script>']);
        $this->trial($membership->workshop);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()
            ->assertDontSee('<script>user()</script>', false)->assertDontSee('<script>workshop()</script>', false)
            ->assertDontSee('<script>plan()</script>', false)->assertSee('&lt;script&gt;plan()&lt;/script&gt;', false);
    }

    public function test_zero_membership_selection_uses_shell_without_fake_tenant_data(): void
    {
        $other = WorkshopMembership::factory()->create();
        $this->actingAs(User::factory()->create())->get('/workshops/select')->assertOk()
            ->assertSee('Menu pengguna')->assertSee('Anda belum tergabung')->assertDontSee($other->workshop->name)
            ->assertDontSee('hari tersisa');
    }
}
