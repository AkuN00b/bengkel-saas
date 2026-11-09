<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function membership(string $role = 'owner'): WorkshopMembership
    {
        $membership = WorkshopMembership::factory()->create(['role' => $role]);
        $this->actingAs($membership->user)->withSession(['active_workshop_id' => $membership->workshop_id]);

        return $membership;
    }

    public function test_guests_cannot_access_customer_routes_or_write_data(): void
    {
        $customer = Customer::factory()->create();
        foreach (['/customers', '/customers/create', '/customers/'.$customer->id, '/customers/'.$customer->id.'/edit'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/customers', ['name' => 'Denied'])->assertRedirect(route('login'));
        $this->put('/customers/'.$customer->id, ['name' => 'Denied'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('customers', 1);
        $this->assertNotSame('Denied', $customer->fresh()->name);
    }

    #[DataProvider('readRoles')]
    public function test_members_can_list_search_and_view_only_their_active_workshop(string $role): void
    {
        $member = $this->membership($role);
        $own = Customer::factory()->create(['workshop_id' => $member->workshop_id, 'name' => 'Own customer']);
        $foreign = Customer::factory()->create(['name' => 'Foreign customer']);

        $this->get('/customers')->assertOk()->assertSee('Own customer')->assertDontSee('Foreign customer');
        $this->get('/customers?q=own')->assertOk()->assertSee('Own customer')->assertDontSee('Foreign customer');
        $this->get('/customers/'.$own->id)->assertOk()->assertSee('Own customer');
        $this->get('/customers/'.$foreign->id)->assertNotFound()->assertDontSee('Foreign customer');
    }

    public static function readRoles(): array
    {
        return [['owner'], ['cashier'], ['mechanic']];
    }

    #[DataProvider('writeRoles')]
    public function test_owner_and_cashier_can_create_and_edit_in_their_workshop(string $role): void
    {
        $member = $this->membership($role);
        $this->get('/customers')->assertOk()->assertSee('Tambah pelanggan');
        $this->get('/customers/create')->assertOk();
        $this->post('/customers', ['name' => '  Pelanggan Baru  ', 'phone' => '+62 812-3456', 'notes' => 'Catatan'])
            ->assertRedirect();
        $customer = Customer::sole();
        $this->assertSame($member->workshop_id, $customer->workshop_id);
        $this->assertSame('Pelanggan Baru', $customer->name);
        $this->get('/customers/'.$customer->id.'/edit')->assertOk()->assertSee('Catatan');
        $this->put('/customers/'.$customer->id, ['name' => 'Nama Diubah', 'phone' => '', 'notes' => ''])
            ->assertRedirect(route('customers.show', $customer))->assertSessionHas('status');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Nama Diubah', 'phone' => null, 'notes' => null]);
    }

    public static function writeRoles(): array
    {
        return [['owner'], ['cashier']];
    }

    public function test_mechanic_cannot_write_even_with_direct_requests_and_forged_role_fields(): void
    {
        $member = $this->membership('mechanic');
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/customers')->assertOk()->assertDontSee('Tambah pelanggan');
        $this->get('/customers/'.$customer->id)->assertOk()->assertDontSee('Ubah pelanggan');
        $this->get('/customers/create')->assertForbidden();
        $this->get('/customers/'.$customer->id.'/edit')->assertForbidden();
        $data = ['name' => 'Denied', 'role' => 'owner', 'is_super_admin' => true];
        $this->postJson('/customers', $data)->assertForbidden();
        $this->putJson('/customers/'.$customer->id, $data)->assertForbidden();
        $this->patchJson('/customers/'.$customer->id, $data)->assertForbidden();
        $this->assertDatabaseCount('customers', 1);
        $this->assertNotSame('Denied', $customer->fresh()->name);
    }

    public function test_platform_admin_has_no_membership_or_write_bypass(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $foreign = Customer::factory()->create();
        $this->actingAs($user)->withSession(['active_workshop_id' => $foreign->workshop_id])
            ->getJson('/customers')->assertForbidden()->assertSessionMissing('active_workshop_id');
        $workshop = Workshop::factory()->create();
        $user->workshops()->attach($workshop, ['role' => 'mechanic']);
        $this->withSession(['active_workshop_id' => $workshop->id])
            ->postJson('/customers', ['name' => 'Denied'])->assertForbidden();
        $this->get('/customers/'.$foreign->id)->assertNotFound();
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_roles_are_not_inherited_between_workshops_and_downgrades_take_effect(): void
    {
        $member = $this->membership();
        $other = Workshop::factory()->create();
        $member->user->workshops()->attach($other, ['role' => 'mechanic']);
        $member->user->load('workshops');
        $this->withSession(['active_workshop_id' => $other->id])
            ->postJson('/customers', ['name' => 'Denied'])->assertForbidden();
        $this->withSession(['active_workshop_id' => $member->workshop_id])
            ->post('/customers', ['name' => 'Allowed'])->assertRedirect();
        $customer = Customer::sole();
        $member->user->workshops()->updateExistingPivot($member->workshop_id, ['role' => 'mechanic']);
        $this->putJson('/customers/'.$customer->id, ['name' => 'Denied'])->assertForbidden();
        $this->assertSame('Allowed', $customer->fresh()->name);
    }

    public function test_revoked_membership_blocks_reads_and_writes_on_the_next_request(): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/customers/'.$customer->id)->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $member->user->workshops()->detach($member->workshop_id);
        $this->getJson('/customers/'.$customer->id)->assertForbidden()->assertDontSee($customer->name)
            ->assertSessionMissing('active_workshop_id');
        $this->postJson('/customers', ['name' => 'Denied'])->assertForbidden();
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_forged_tenant_inputs_cannot_redirect_customer_reads_or_writes(): void
    {
        $member = $this->membership();
        $foreign = Customer::factory()->create(['name' => 'Foreign private customer']);
        $data = ['name' => 'Own customer', 'workshop_id' => $foreign->workshop_id, 'id' => $foreign->id,
            'role' => 'super_admin', 'created_at' => '2000-01-01', 'is_super_admin' => true];
        $this->post('/customers', $data)->assertRedirect();
        $own = Customer::where('workshop_id', $member->workshop_id)->sole();
        $this->assertNotSame($foreign->id, $own->id);
        $this->put('/customers/'.$own->id, array_replace($data, ['name' => 'Updated']))->assertRedirect();
        $this->assertSame($member->workshop_id, $own->fresh()->workshop_id);
        $this->assertSame('Foreign private customer', $foreign->fresh()->name);
        $this->assertFalse($member->user->fresh()->isSuperAdmin());
        $this->withHeader('X-Workshop-Id', $foreign->workshop_id)
            ->get('/customers?workshop_id='.$foreign->workshop_id.'&active_workshop_id='.$foreign->workshop_id)
            ->assertOk()->assertSee('Updated')->assertDontSee('Foreign private customer');
        $this->get('/customers/'.$foreign->id.'/edit')->assertNotFound();
        $this->putJson('/customers/'.$foreign->id, ['name' => 'Denied'])->assertNotFound();
    }

    public function test_customer_ids_are_scoped_to_active_workshop_even_with_membership_in_both(): void
    {
        $member = $this->membership();
        $other = Workshop::factory()->create();
        $member->user->workshops()->attach($other, ['role' => 'owner']);
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/customers/'.$customer->id.'/edit')->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/workshops/active', ['workshop_id' => $other->id])->assertRedirect();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/customers/'.$customer->id)->assertNotFound();
        $this->putJson('/customers/'.$customer->id, ['name' => 'Stale form'])->assertNotFound();
        $this->get('/customers')->assertOk()->assertDontSee($customer->name);
        $this->assertNotSame('Stale form', $customer->fresh()->name);
    }

    public function test_cross_tenant_reads_and_updates_are_rejected_in_both_directions(): void
    {
        $a = WorkshopMembership::factory()->create(['role' => 'owner']);
        $b = WorkshopMembership::factory()->create(['role' => 'owner']);
        $customerA = Customer::factory()->create(['workshop_id' => $a->workshop_id]);
        $customerB = Customer::factory()->create(['workshop_id' => $b->workshop_id]);

        foreach ([[$a, $customerB], [$b, $customerA]] as [$member, $foreign]) {
            $this->actingAs($member->user)->withSession(['active_workshop_id' => $member->workshop_id]);
            $this->get('/customers/'.$foreign->id)->assertNotFound()->assertDontSee($foreign->name);
            $this->putJson('/customers/'.$foreign->id, ['name' => 'Denied'])->assertNotFound();
            $this->assertNotSame('Denied', $foreign->fresh()->name);
        }
    }

    public function test_members_with_no_active_choice_are_sent_to_selection_before_customer_access(): void
    {
        $user = User::factory()->create();
        $foreign = Customer::factory()->create();
        $this->actingAs($user)->get('/customers')->assertRedirect(route('workshops.select'));
        $this->getJson('/customers/'.$foreign->id)->assertForbidden()->assertDontSee($foreign->name);
        $this->postJson('/customers', ['name' => 'Denied'])->assertForbidden();
        $workshops = Workshop::factory()->count(2)->create();
        foreach ($workshops as $workshop) {
            $user->workshops()->attach($workshop, ['role' => 'owner']);
        }
        $this->get('/customers/create')->assertRedirect(route('workshops.select'));
        $this->assertDatabaseCount('customers', 1);
    }

    #[DataProvider('invalidCustomerIds')]
    public function test_invalid_missing_or_overflowing_customer_ids_return_not_found(string $id): void
    {
        $this->membership();
        $this->get('/customers/'.rawurlencode($id))->assertNotFound();
        $this->get('/customers/'.rawurlencode($id).'/edit')->assertNotFound();
        $this->putJson('/customers/'.rawurlencode($id), ['name' => 'Denied'])->assertNotFound();
    }

    public static function invalidCustomerIds(): array
    {
        return [['0'], ['-1'], ['missing'], ['1 OR 1=1'], ['999999999'], ['9999999999999999999'], ['999999999999999999999999999']];
    }

    #[DataProvider('invalidCustomerData')]
    public function test_invalid_customer_input_is_rejected_on_both_create_and_update(array $data, string $field): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $original = $customer->fresh()->getAttributes();
        $payload = array_replace(['name' => 'Valid name'], $data);
        $this->postJson('/customers', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->putJson('/customers/'.$customer->id, $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($original, $customer->fresh()->getAttributes());
    }

    public static function invalidCustomerData(): array
    {
        return [
            'blank name' => [['name' => '   '], 'name'],
            'missing name' => [['name' => null], 'name'],
            'array name' => [['name' => ['invalid']], 'name'],
            'long name' => [['name' => str_repeat('n', 256)], 'name'],
            'null byte in name' => [['name' => "Name\0bad"], 'name'],
            'array phone' => [['phone' => ['081234']], 'phone'],
            'numeric phone loses zero' => [['phone' => 812345], 'phone'],
            'long phone' => [['phone' => str_repeat('1', 33)], 'phone'],
            'invalid phone' => [['phone' => '<script>bad()</script>'], 'phone'],
            'no digits' => [['phone' => '+---'], 'phone'],
            'array notes' => [['notes' => ['invalid']], 'notes'],
            'long notes' => [['notes' => str_repeat('n', 5001)], 'notes'],
            'null byte in notes' => [['notes' => "Notes\0bad"], 'notes'],
        ];
    }

    public function test_optional_fields_and_duplicate_names_and_phones_are_allowed(): void
    {
        $member = $this->membership();
        $this->post('/customers', ['name' => 'Same name'])->assertRedirect();
        $this->assertDatabaseHas('customers', ['workshop_id' => $member->workshop_id, 'phone' => null, 'notes' => null]);
        for ($i = 0; $i < 2; $i++) {
            $this->post('/customers', ['name' => 'Same name', 'phone' => '08123456'])->assertRedirect();
        }
        $this->assertDatabaseCount('customers', 3);
    }

    public function test_unicode_names_and_supported_field_limits_are_preserved(): void
    {
        $this->membership();
        $data = ['name' => str_repeat('é', 255), 'phone' => '0'.str_repeat('1', 31), 'notes' => str_repeat('n', 5000)];
        $this->post('/customers', $data)->assertRedirect();
        $customer = Customer::sole();
        $this->assertSame($data['name'], $customer->name);
        $this->assertSame($data['phone'], $customer->phone);
        $this->assertSame($data['notes'], $customer->notes);
    }

    public function test_search_matches_name_and_phone_without_or_leaking_other_tenants(): void
    {
        $member = $this->membership();
        Customer::factory()->create(['workshop_id' => $member->workshop_id, 'name' => 'Adi', 'phone' => '0812345678']);
        Customer::factory()->create(['name' => 'PRIVATE FOREIGN', 'phone' => '0812345678']);
        foreach (['aDi', '123456'] as $search) {
            $this->get('/customers?q='.$search)->assertOk()->assertSee('Adi')->assertDontSee('PRIVATE FOREIGN');
        }
        $this->get('/customers?q=PRIVATE')->assertOk()->assertSee('Pelanggan tidak ditemukan')
            ->assertDontSee('PRIVATE FOREIGN');
    }

    public function test_search_escapes_like_metacharacters_and_sql_input_is_literal(): void
    {
        $member = $this->membership();
        Customer::factory()->create(['workshop_id' => $member->workshop_id, 'name' => 'Literal %_\\ name']);
        Customer::factory()->create(['workshop_id' => $member->workshop_id, 'name' => 'Ordinary name']);
        foreach (['%', '_', '\\'] as $search) {
            $this->get('/customers?q='.rawurlencode($search))->assertOk()
                ->assertViewHas('customers', fn ($page) => $page->total() === 1)->assertSee('Literal')->assertDontSee('Ordinary name');
        }
        $this->get('/customers?q='.rawurlencode("' OR 1=1 --"))->assertOk()
            ->assertViewHas('customers', fn ($page) => $page->total() === 0);
    }

    public function test_pagination_is_stable_bounded_and_preserves_the_search_query(): void
    {
        $member = $this->membership();
        $customers = Customer::factory()->count(21)->create(['workshop_id' => $member->workshop_id, 'name' => 'Repeated name']);
        Customer::factory()->count(3)->create(['name' => 'Repeated name']);
        $this->get('/customers?q=repeated')->assertOk()
            ->assertViewHas('customers', fn ($page) => $page->total() === 21 && $page->count() === 20
                && $page->first()->id === $customers->first()->id && str_contains($page->nextPageUrl(), 'q=repeated'));
        $this->get('/customers?q=repeated&page=2')->assertOk()
            ->assertViewHas('customers', fn ($page) => $page->total() === 21 && $page->count() === 1
                && $page->first()->id === $customers->last()->id);
    }

    #[DataProvider('invalidSearchData')]
    public function test_invalid_search_input_is_rejected(string $query, string $field): void
    {
        $this->membership();
        $this->getJson('/customers?'.$query)->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function invalidSearchData(): array
    {
        return [['q[]=bad', 'q'], ['q='.str_repeat('x', 101), 'q'], ['q=bad%00query', 'q'], ['page[]=bad', 'page'], ['page=-1', 'page'], ['page=99999999999999999999', 'page']];
    }

    public function test_customer_fields_search_and_validation_old_input_are_escaped_and_safe(): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id,
            'name' => '<script>bad()</script>', 'notes' => '<img src=x onerror=bad()>']);
        foreach (['/customers', '/customers/'.$customer->id, '/customers/'.$customer->id.'/edit'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('<script>bad()</script>', false)->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false);
        }
        $this->get('/customers/'.$customer->id)->assertDontSee('<img src=x onerror=bad()>', false);
        $this->get('/customers?q='.rawurlencode('"><script>bad()</script>'))->assertOk()->assertDontSee('<script>bad()</script>', false);
        $this->from('/customers/create')->post('/customers', ['name' => ['bad'], 'notes' => ['bad'], 'phone' => ['bad']])
            ->assertRedirect('/customers/create')->assertSessionHasErrors(['name', 'phone', 'notes']);
        $this->withCookie(config('session.cookie'), session()->getId())->get('/customers/create')->assertOk();
    }

    #[DataProvider('readRoles')]
    public function test_deletion_is_unavailable_for_every_role(string $role): void
    {
        $member = $this->membership($role);
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/customers/'.$customer->id)->assertOk()->assertDontSee('Hapus');
        $this->delete('/customers/'.$customer->id)->assertMethodNotAllowed();
        $this->assertFalse(Gate::forUser($member->user)->allows('delete', $customer));
        $this->assertModelExists($customer);
    }

    public function test_customer_update_requires_csrf_outside_the_testing_bypass(): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->app->detectEnvironment(fn () => 'local');
        $this->put('/customers/'.$customer->id, ['name' => 'Denied'])->assertStatus(419);
        $this->assertNotSame('Denied', $customer->fresh()->name);
    }
}
