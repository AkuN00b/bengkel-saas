<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function membership(string $role = 'owner'): WorkshopMembership
    {
        $member = WorkshopMembership::factory()->create(['role' => $role]);
        $this->actingAs($member->user)->withSession(['active_workshop_id' => $member->workshop_id]);

        return $member;
    }

    private function data(WorkshopMembership $member, array $overrides = []): array
    {
        return array_replace(['plate_number' => 'b 1234 abc', 'workshop_context' => $member->workshop_id], $overrides);
    }

    public function test_guests_cannot_use_vehicle_routes_or_customer_options(): void
    {
        $vehicle = Vehicle::factory()->create();
        foreach (['/vehicles', '/vehicles/create', '/vehicles/'.$vehicle->id, '/vehicles/'.$vehicle->id.'/edit',
            '/vehicles/lookup?plate_number=B1234ABC', '/vehicles/customer-options'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        $this->post('/vehicles', [])->assertRedirect(route('login'));
        $this->put('/vehicles/'.$vehicle->id, [])->assertRedirect(route('login'));
    }

    #[DataProvider('writeRoles')]
    public function test_writers_create_normalized_vehicles_with_or_without_a_customer_and_edit_them(string $role): void
    {
        $member = $this->membership($role);
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/vehicles/create')->assertOk()->assertSee('Pelanggan (opsional)');
        $this->post('/vehicles', $this->data($member))->assertRedirect();
        $vehicle = Vehicle::sole();
        $this->assertSame($member->workshop_id, $vehicle->workshop_id);
        $this->assertSame('B 1234 ABC', $vehicle->plate_number);
        $this->assertSame('B1234ABC', $vehicle->normalized_plate_number);
        $this->assertNull($vehicle->customer_id);
        $this->put('/vehicles/'.$vehicle->id, $this->data($member, [
            'plate_number' => 'ab 001 cd', 'customer_id' => $customer->id, 'brand' => 'Honda',
            'model' => 'Vario', 'year' => 2020, 'notes' => "Baris satu\nBaris dua",
        ]))->assertRedirect(route('vehicles.show', $vehicle));
        $this->get('/vehicles/'.$vehicle->id)->assertOk()->assertSee($customer->name)->assertSee('Honda Vario')->assertSee('2020');
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'customer_id' => $customer->id, 'normalized_plate_number' => 'AB001CD']);
        $this->put('/vehicles/'.$vehicle->id, $this->data($member, [
            'plate_number' => 'AB001CD', 'customer_id' => '', 'brand' => '', 'model' => '', 'year' => '', 'notes' => '',
        ]))->assertRedirect();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'customer_id' => null, 'brand' => null, 'year' => null]);
        $this->assertDatabaseCount('vehicles', 1);
    }

    public static function writeRoles(): array
    {
        return [['owner'], ['cashier']];
    }

    #[DataProvider('readRoles')]
    public function test_readers_are_isolated_to_the_active_workshop_on_lists_searches_and_records(string $role): void
    {
        $member = $this->membership($role);
        $own = Vehicle::factory()->create(['workshop_id' => $member->workshop_id, 'plate_number' => 'B 1234 ABC']);
        $foreign = Vehicle::factory()->create(['plate_number' => 'D 999 XX']);
        $this->get('/vehicles')->assertOk()->assertSee('B 1234 ABC')->assertDontSee('D 999 XX');
        $this->get('/vehicles?q=b%201234')->assertOk()->assertSee('B 1234 ABC')->assertDontSee('D 999 XX');
        $this->get('/vehicles/'.$own->id)->assertOk();
        $this->get('/vehicles/'.$foreign->id)->assertNotFound()->assertDontSee('D 999 XX');
        $this->get('/vehicles/lookup?plate_number=b1234abc')->assertRedirect(route('vehicles.show', $own));
        $this->get('/vehicles/lookup?plate_number=d999xx')->assertOk()->assertSee('Kendaraan belum ditemukan');
    }

    public static function readRoles(): array
    {
        return [['owner'], ['cashier'], ['mechanic']];
    }

    public function test_mechanic_cannot_write_or_query_the_write_only_customer_picker(): void
    {
        $member = $this->membership('mechanic');
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/vehicles')->assertOk()->assertDontSee('Tambah kendaraan');
        $this->get('/vehicles/'.$vehicle->id)->assertOk()->assertDontSee('Ubah kendaraan');
        $this->get('/vehicles/create')->assertForbidden();
        $this->get('/vehicles/'.$vehicle->id.'/edit')->assertForbidden();
        $this->getJson('/vehicles/customer-options')->assertForbidden();
        $this->postJson('/vehicles', $this->data($member, ['role' => 'owner']))->assertForbidden();
        $this->putJson('/vehicles/'.$vehicle->id, $this->data($member))->assertForbidden();
        $this->get('/vehicles/lookup?plate_number=Z999XX')->assertOk()
            ->assertDontSee('Tambah kendaraan dengan pelat ini')->assertSee('Minta pemilik atau kasir');
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_foreign_and_missing_customer_ids_are_rejected_without_leaking_customer_details(): void
    {
        $member = $this->membership();
        $foreign = Customer::factory()->create(['name' => 'FOREIGN SECRET']);
        foreach ([$foreign->id, 99999999, '999999999999999999999999', ['bad']] as $id) {
            $this->postJson('/vehicles', $this->data($member, ['customer_id' => $id]))
                ->assertUnprocessable()->assertJsonValidationErrors('customer_id')->assertDontSee('FOREIGN SECRET');
        }
        $this->get('/vehicles/create?customer_id='.$foreign->id)->assertRedirect()->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_foreign_vehicle_ids_cannot_be_read_edited_or_updated(): void
    {
        $member = $this->membership();
        $foreign = Vehicle::factory()->create();
        $this->get('/vehicles/'.$foreign->id)->assertNotFound();
        $this->get('/vehicles/'.$foreign->id.'/edit')->assertNotFound();
        $this->putJson('/vehicles/'.$foreign->id, $this->data($member))->assertNotFound();
        $this->patchJson('/vehicles/'.$foreign->id, $this->data($member))->assertNotFound();
        $this->assertNotSame('B1234ABC', $foreign->fresh()->normalized_plate_number);
    }

    public function test_forged_ownership_and_normalized_fields_cannot_change_server_owned_values(): void
    {
        $member = $this->membership();
        $foreign = Vehicle::factory()->create();
        $this->withHeader('X-Workshop-Id', $foreign->workshop_id)
            ->post('/vehicles?workshop_id='.$foreign->workshop_id, $this->data($member, [
                'id' => $foreign->id, 'workshop_id' => $foreign->workshop_id,
                'normalized_plate_number' => 'FORGED', 'created_at' => '2000-01-01',
            ]))->assertRedirect();
        $own = Vehicle::where('workshop_id', $member->workshop_id)->sole();
        $this->assertSame('B1234ABC', $own->normalized_plate_number);
        $this->assertNotSame($foreign->id, $own->id);
        $this->assertNotSame('2000-01-01', $own->created_at->toDateString());
    }

    public function test_duplicate_plates_are_rejected_on_create_and_update_but_not_across_workshops(): void
    {
        $member = $this->membership();
        $own = Vehicle::factory()->create(['workshop_id' => $member->workshop_id, 'plate_number' => 'B 1234 ABC']);
        $other = Vehicle::factory()->create(['workshop_id' => $member->workshop_id, 'plate_number' => 'D 1234 EF']);
        $this->postJson('/vehicles', $this->data($member))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        $this->putJson('/vehicles/'.$other->id, $this->data($member))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        $this->put('/vehicles/'.$own->id, $this->data($member))->assertRedirect();
        Vehicle::factory()->create(['plate_number' => 'F 987 GH']);
        $this->post('/vehicles', $this->data($member, ['plate_number' => 'f987gh']))->assertRedirect();
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_vehicle_input_is_rejected_without_saving(array $invalid, string $field): void
    {
        $member = $this->membership();
        $this->postJson('/vehicles', $this->data($member, $invalid))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public static function invalidInputs(): array
    {
        return [
            [['plate_number' => ''], 'plate_number'], [['plate_number' => ['bad']], 'plate_number'],
            [['plate_number' => 'ABC'], 'plate_number'], [['plate_number' => '1234'], 'plate_number'],
            [['plate_number' => 'B-123-AB'], 'plate_number'], [['plate_number' => "B\n123 AB"], 'plate_number'],
            [['plate_number' => 'B'.str_repeat('1', 20)], 'plate_number'], [['plate_number' => "B123\0AB"], 'plate_number'],
            [['plate_number' => 'В 123 AB'], 'plate_number'], [['brand' => str_repeat('A', 101)], 'brand'],
            [['brand' => ['bad']], 'brand'], [['model' => "Bad\0model"], 'model'],
            [['model' => str_repeat('A', 101)], 'model'], [['year' => 1899], 'year'],
            [['year' => 2101], 'year'], [['year' => '2020.5'], 'year'], [['year' => ['bad']], 'year'],
            [['notes' => str_repeat('A', 5001)], 'notes'], [['notes' => ['bad']], 'notes'],
            [['workshop_context' => 999999], 'workshop_context'], [['workshop_context' => null], 'workshop_context'],
        ];
    }

    public function test_options_are_tenant_scoped_paginated_and_do_not_treat_wildcards_as_sql(): void
    {
        $member = $this->membership();
        Customer::factory()->count(21)->create(['workshop_id' => $member->workshop_id]);
        $literal = Customer::factory()->create(['workshop_id' => $member->workshop_id, 'name' => 'Literal %_ person']);
        $foreign = Customer::factory()->create(['name' => 'FOREIGN SECRET']);
        $this->getJson('/vehicles/customer-options')->assertOk()->assertJsonCount(20, 'customers')
            ->assertJsonPath('next_page', 2)->assertJsonPath('workshop_id', $member->workshop_id)->assertDontSee($foreign->name);
        $this->getJson('/vehicles/customer-options?page=2')->assertOk()->assertJsonCount(2, 'customers')->assertJsonPath('next_page', null);
        $this->getJson('/vehicles/customer-options?q=%25_')->assertOk()->assertJsonCount(1, 'customers')
            ->assertJsonPath('customers.0.id', $literal->id);
        $this->getJson('/vehicles/customer-options?q[]=bad')->assertUnprocessable();
        $this->getJson('/vehicles/customer-options?q='.urlencode("ab\0c"))->assertUnprocessable();
        $this->getJson('/vehicles/customer-options?page=999999999999')->assertUnprocessable();
    }

    public function test_lookup_prefills_creation_and_customer_detail_links_only_its_own_vehicles(): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $own = Vehicle::factory()->create(['workshop_id' => $member->workshop_id, 'customer_id' => $customer->id]);
        $other = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/vehicles/lookup?plate_number=ab001cd')->assertOk()->assertSee('Kendaraan belum ditemukan')
            ->assertSee(route('vehicles.create', ['plate_number' => 'AB001CD']), false);
        $this->get('/vehicles/create?plate_number=ab001cd&customer_id='.$customer->id)->assertOk()
            ->assertViewHas('selectedCustomer', fn ($selected) => $selected->is($customer))
            ->assertSee('value="ab001cd"', false);
        $this->get('/customers/'.$customer->id)->assertOk()->assertSee($own->plate_number)->assertDontSee($other->plate_number);
        $this->get('/customers/'.$customer->id.'?vehicles_page=999999999999')->assertRedirect()->assertSessionHasErrors('vehicles_page');
    }

    public function test_vehicle_list_and_customer_vehicle_pagination_are_bounded(): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        Vehicle::factory()->count(21)->create(['workshop_id' => $member->workshop_id, 'customer_id' => $customer->id]);
        $this->get('/vehicles?q=B&page=2')->assertOk()
            ->assertViewHas('vehicles', fn ($page) => $page->count() === 1 && $page->total() === 21)
            ->assertSee('q=B', false);
        $this->get('/customers/'.$customer->id)->assertOk()
            ->assertViewHas('vehicles', fn ($page) => $page->count() === 10 && $page->total() === 21);
    }

    public function test_role_changes_revocation_and_platform_admin_do_not_bypass_vehicle_security(): void
    {
        $member = $this->membership();
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $member->user->load('workshops');
        $member->workshop->users()->updateExistingPivot($member->user_id, ['role' => 'mechanic']);
        $this->postJson('/vehicles', $this->data($member))->assertForbidden();
        $this->putJson('/vehicles/'.$vehicle->id, $this->data($member))->assertForbidden();
        $member->user->workshops()->detach($member->workshop_id);
        $this->getJson('/vehicles')->assertForbidden();
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();
        $this->actingAs($admin)->withSession(['active_workshop_id' => $member->workshop_id])
            ->getJson('/vehicles')->assertForbidden();
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_switching_workshops_rejects_stale_forms_and_foreign_customers(): void
    {
        $member = $this->membership();
        $other = Workshop::factory()->create();
        $member->user->workshops()->attach($other, ['role' => 'owner']);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->withSession(['active_workshop_id' => $other->id]);
        $this->postJson('/vehicles', $this->data($member))->assertUnprocessable()->assertJsonValidationErrors('workshop_context');
        $this->putJson('/vehicles/'.$vehicle->id, $this->data($member))->assertUnprocessable();
        $this->putJson('/vehicles/'.$vehicle->id, $this->data($member, ['workshop_context' => $other->id]))->assertNotFound();
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_errors_preserve_input_and_escaped_values_cannot_execute_html(): void
    {
        $member = $this->membership();
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id, 'name' => '<script>customer()</script>']);
        $this->from('/vehicles/create')->post('/vehicles', $this->data($member, [
            'brand' => '<script>brand()</script>', 'notes' => "Two\nlines", 'year' => 1, 'customer_id' => $customer->id,
        ]))->assertRedirect('/vehicles/create')->assertSessionHasErrors('year');
        $this->withCookie(config('session.cookie'), session()->getId())->get('/vehicles/create')
            ->assertOk()->assertSee('Two')->assertSee('&lt;script&gt;brand()&lt;/script&gt;', false)->assertDontSee('<script>customer()</script>', false);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id, 'brand' => '<script>brand()</script>']);
        $this->get('/vehicles/'.$vehicle->id)->assertOk()->assertDontSee('<script>brand()</script>', false);
    }

    #[DataProvider('readRoles')]
    public function test_deletion_is_unavailable_for_all_roles(string $role): void
    {
        $member = $this->membership($role);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->get('/vehicles/'.$vehicle->id)->assertOk()->assertDontSee('Hapus');
        $this->delete('/vehicles/'.$vehicle->id)->assertMethodNotAllowed();
        $this->assertFalse(Gate::forUser($member->user)->allows('delete', $vehicle));
        $this->assertModelExists($vehicle);
    }

    #[DataProvider('invalidUrls')]
    public function test_searches_and_ids_reject_arrays_null_bytes_and_overflow_safely(string $url, int $status): void
    {
        $this->membership();
        $this->getJson($url)->assertStatus($status);
    }

    public static function invalidUrls(): array
    {
        return [
            ['/vehicles/99999999999999999999999', 404], ['/vehicles/0', 404],
            ['/vehicles?q[]=bad', 422], ['/vehicles?q=B%00123', 422], ['/vehicles?page=999999999999', 422],
            ['/vehicles/lookup?plate_number[]=bad', 422], ['/vehicles/lookup?plate_number=B-1234', 422],
        ];
    }

    public function test_concurrent_duplicate_is_reported_as_validation_error_instead_of_server_error(): void
    {
        $member = $this->membership();
        Vehicle::creating(function (): void {
            throw new UniqueConstraintViolationException('pgsql', 'insert vehicle', [], new \Exception('unique conflict', 23505));
        });
        try {
            $this->postJson('/vehicles', $this->data($member))->assertUnprocessable()->assertJsonValidationErrors('plate_number');
        } finally {
            Vehicle::flushEventListeners();
            Vehicle::clearBootedModels();
        }
    }

    public function test_vehicle_update_requires_csrf_outside_the_testing_bypass(): void
    {
        $member = $this->membership();
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->app->detectEnvironment(fn () => 'local');
        $this->put('/vehicles/'.$vehicle->id, $this->data($member))->assertStatus(419);
    }
}
