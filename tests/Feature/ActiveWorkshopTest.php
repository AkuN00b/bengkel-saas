<?php

namespace Tests\Feature;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActiveWorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_workshop_is_activated_and_only_its_data_is_rendered(): void
    {
        $a = WorkshopMembership::factory()->create();
        $b = WorkshopMembership::factory()->create();
        $this->actingAs($a->user)->get('/dashboard')->assertOk()
            ->assertSee($a->workshop->name)->assertDontSee($b->workshop->name)
            ->assertSessionHas('active_workshop_id', $a->workshop_id);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->actingAs($b->user)->get('/dashboard')->assertRedirect(route('workshops.select'))
            ->assertSessionMissing('active_workshop_id')->assertDontSee($a->workshop->name);
        $this->get('/dashboard')->assertOk()->assertSee($b->workshop->name)
            ->assertDontSee($a->workshop->name)->assertSessionHas('active_workshop_id', $b->workshop_id);
    }

    public function test_multiple_memberships_require_selection_and_list_only_owned_memberships(): void
    {
        $user = User::factory()->create();
        [$first, $second] = Workshop::factory()->count(2)->create()->all();
        $user->workshops()->attach([$first->id => ['role' => 'owner'], $second->id => ['role' => 'mechanic']]);
        $other = WorkshopMembership::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('workshops.select'))
            ->assertSessionMissing('active_workshop_id');
        $this->get('/workshops/select')->assertOk()->assertSee($first->name)->assertSee($second->name)
            ->assertDontSee($other->workshop->name);
        $this->getJson('/dashboard')->assertForbidden()->assertDontSee($first->name);
    }

    public function test_zero_memberships_has_a_safe_landing_page_and_logout_available(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect(route('workshops.select'));
        $this->get('/workshops/select')->assertOk()->assertSee('Anda belum tergabung dalam bengkel.');
        $this->getJson('/dashboard')->assertForbidden();
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_paginated_selection_does_not_leak_other_users_workshops(): void
    {
        $user = User::factory()->create();
        $workshops = Workshop::factory()->count(21)->create();
        foreach ($workshops as $workshop) {
            $user->workshops()->attach($workshop, ['role' => 'mechanic']);
        }
        $foreign = WorkshopMembership::factory()->create();
        $this->actingAs($user)->get('/workshops/select?page=2')->assertOk()
            ->assertViewHas('workshops', fn ($page) => $page->total() === 21 && $page->count() === 1)
            ->assertDontSee($foreign->workshop->name);
    }

    public function test_switching_uses_verified_membership_and_rotates_session(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $other = Workshop::factory()->create();
        $membership->user->workshops()->attach($other, ['role' => 'cashier']);
        $this->actingAs($membership->user)->withSession(['active_workshop_id' => $membership->workshop_id])
            ->get('/dashboard')->assertOk();
        $oldId = session()->getId();
        $this->withCookie(config('session.cookie'), $oldId);
        $this->post('/workshops/active', ['workshop_id' => $other->id, 'redirect' => 'https://untrusted.example'])
            ->assertRedirect(route('dashboard'))->assertSessionHas('active_workshop_id', $other->id);
        $this->assertNotSame($oldId, session()->getId());
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/dashboard')->assertOk()->assertSee($other->name)->assertDontSee($membership->workshop->name);
    }

    public function test_other_tenant_and_missing_ids_cannot_be_selected_or_enumerated(): void
    {
        $a = WorkshopMembership::factory()->create();
        $b = WorkshopMembership::factory()->create();
        $this->actingAs($a->user)->withSession(['active_workshop_id' => $a->workshop_id])->get('/dashboard');
        $this->withCookie(config('session.cookie'), session()->getId());
        foreach ([$b->workshop_id, 999999999] as $id) {
            $this->postJson('/workshops/active', ['workshop_id' => $id])->assertNotFound()
                ->assertSessionHas('active_workshop_id', $a->workshop_id)->assertDontSee($b->workshop->name);
        }
        $this->actingAs($b->user)->postJson('/workshops/active', ['workshop_id' => $a->workshop_id])->assertNotFound();
    }

    #[DataProvider('invalidSessionIds')]
    public function test_tampered_session_is_cleared_before_tenant_data_is_accessed(mixed $value): void
    {
        $a = WorkshopMembership::factory()->create();
        $other = WorkshopMembership::factory()->create();
        $selected = $value === 'other-tenant' ? $other->workshop_id : $value;
        $this->actingAs($a->user)->withSession(['active_workshop_id' => $selected])->getJson('/dashboard')
            ->assertForbidden()->assertSessionMissing('active_workshop_id')
            ->assertDontSee($other->workshop->name)->assertDontSee($a->workshop->name);
    }

    public static function invalidSessionIds(): array
    {
        return [['other-tenant'], [999999999], [-1], [0], [true], [['id' => 1]], ['9999999999999999999999999'], ['1 OR 1=1']];
    }

    #[DataProvider('invalidRequestIds')]
    public function test_invalid_switch_input_is_rejected_without_changing_context(mixed $id): void
    {
        $membership = WorkshopMembership::factory()->create();
        $this->actingAs($membership->user)->withSession(['active_workshop_id' => $membership->workshop_id])
            ->postJson('/workshops/active', ['workshop_id' => $id])->assertUnprocessable()
            ->assertSessionHas('active_workshop_id', $membership->workshop_id);
    }

    public static function invalidRequestIds(): array
    {
        return [[null], [-1], ['invalid'], [['id' => 1]], ['9999999999999999999999999']];
    }

    public function test_query_and_header_ids_do_not_override_verified_session(): void
    {
        $a = WorkshopMembership::factory()->create();
        $b = WorkshopMembership::factory()->create();
        $this->actingAs($a->user)->withSession(['active_workshop_id' => $a->workshop_id])
            ->withHeader('X-Workshop-Id', $b->workshop_id)
            ->get('/dashboard?workshop_id='.$b->workshop_id.'&active_workshop_id='.$b->workshop_id)
            ->assertOk()->assertSee($a->workshop->name)->assertDontSee($b->workshop->name)
            ->assertSessionHas('active_workshop_id', $a->workshop_id);
    }

    public function test_revoked_membership_clears_context_even_with_cached_relationships(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $user = $membership->user;
        $workshop = $membership->workshop;
        $user->load('workshops');
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $user->workshops()->detach($workshop);
        $this->get('/dashboard')->assertRedirect(route('workshops.select'))->assertSessionMissing('active_workshop_id')
            ->assertDontSee($workshop->name);
        $this->postJson('/workshops/active', ['workshop_id' => $workshop->id])->assertNotFound();
    }

    public function test_deleted_workshop_is_not_resolved_from_old_session(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $this->actingAs($membership->user)->get('/dashboard')->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $membership->delete();
        $membership->workshop->delete();
        $this->getJson('/dashboard')->assertForbidden()->assertSessionMissing('active_workshop_id');
    }

    #[DataProvider('memberRoles')]
    public function test_each_member_role_can_resolve_its_own_workshop(WorkshopRole $role): void
    {
        $membership = WorkshopMembership::factory()->create(['role' => $role]);
        $this->actingAs($membership->user)->get('/dashboard')->assertOk()->assertSee($membership->workshop->name);
    }

    public static function memberRoles(): array
    {
        return [[WorkshopRole::Owner], [WorkshopRole::Cashier], [WorkshopRole::Mechanic]];
    }

    public function test_platform_admin_does_not_bypass_membership(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_super_admin' => true])->save();
        $other = WorkshopMembership::factory()->create();
        $this->actingAs($user)->withSession(['active_workshop_id' => $other->workshop_id])->getJson('/dashboard')->assertForbidden();
        $this->postJson('/workshops/active', ['workshop_id' => $other->workshop_id])->assertNotFound();
    }

    public function test_guests_cannot_list_or_select_workshops(): void
    {
        $this->get('/workshops/select')->assertRedirect(route('login'));
        $this->post('/workshops/active', ['workshop_id' => 1])->assertRedirect(route('login'));
        $this->postJson('/workshops/active', ['workshop_id' => 1])->assertUnauthorized();
    }

    public function test_login_discards_previous_tenant_context_before_resolving_new_user(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $this->withSession(['active_workshop_id' => 999999999])->post('/login', [
            'email' => $membership->user->email, 'password' => 'password',
        ])->assertRedirect(route('dashboard'))->assertSessionMissing('active_workshop_id');
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/dashboard')->assertOk()->assertSessionHas('active_workshop_id', $membership->workshop_id);
    }

    public function test_workshop_names_are_escaped_in_selection_and_dashboard(): void
    {
        $membership = WorkshopMembership::factory()->create();
        $membership->workshop->update(['name' => '<script>alert(1)</script>']);
        $this->actingAs($membership->user)->get('/workshops/select')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/dashboard')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
