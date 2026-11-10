<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\SparePart;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InventoryFixtures;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use InventoryFixtures, RefreshDatabase;

    #[DataProvider('catalogs')]
    public function test_owner_manages_catalog_while_cashier_reads_and_mechanic_is_denied(string $prefix): void
    {
        $owner = $this->owner();
        $data = ['workshop_context' => $owner->workshop_id, 'name' => 'Katalog Baru', 'default_price' => 10000, 'selling_price' => 25000, 'unit' => 'pcs', 'sku' => ' ab-12 ', 'current_stock' => 900, 'is_active' => false, 'workshop_id' => 999, 'version' => 99];
        $this->get('/'.$prefix.'/create')->assertOk();
        $this->post('/'.$prefix, $data)->assertRedirect();
        $class = $prefix === 'services' ? Service::class : SparePart::class;
        $record = $class::sole();
        $this->assertSame($owner->workshop_id, $record->workshop_id);
        $this->assertTrue($record->is_active);
        $this->assertSame(1, $record->version);
        if ($prefix === 'spare-parts') {
            $this->assertSame(0, $record->current_stock);
            $this->assertSame('AB-12', $record->sku);
        }
        $this->get('/'.$prefix.'/'.$record->id)->assertOk()->assertSee('Katalog Baru');
        $this->put('/'.$prefix.'/'.$record->id, array_replace($data, ['name' => 'Updated', 'sku' => 'AB-12', 'version' => 1]))->assertRedirect();
        $this->patch('/'.$prefix.'/'.$record->id.'/activation', ['workshop_context' => $owner->workshop_id, 'version' => 2, 'is_active' => false])->assertRedirect();
        $this->assertFalse($record->fresh()->is_active);
        $this->get('/'.$prefix)->assertDontSee('Updated');
        $this->get('/'.$prefix.'?status=inactive')->assertSee('Updated');
        $this->delete('/'.$prefix.'/'.$record->id)->assertStatus(405);
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->get('/'.$prefix)->assertOk();
        $this->get('/'.$prefix.'/'.$record->id)->assertOk()->assertDontSee('Ubah katalog');
        $this->get('/'.$prefix.'/create')->assertForbidden();
        $this->post('/'.$prefix, $data)->assertForbidden();
        $this->put('/'.$prefix.'/'.$record->id, $data)->assertForbidden();
        $this->patch('/'.$prefix.'/'.$record->id.'/activation', ['workshop_context' => $owner->workshop_id, 'version' => 3, 'is_active' => true])->assertForbidden();
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'mechanic']);
        $mechanic->user->forceFill(['is_super_admin' => true])->save();
        $this->actingAs($mechanic->user)->get('/'.$prefix)->assertForbidden();
        $this->get('/'.$prefix.'/'.$record->id)->assertForbidden();
    }

    public static function catalogs(): array
    {
        return [['services'], ['spare-parts']];
    }

    public function test_sku_is_unique_per_workshop_nullable_and_unit_is_immutable(): void
    {
        $owner = $this->owner();
        $data = ['workshop_context' => $owner->workshop_id, 'name' => 'Busi', 'unit' => 'pcs', 'selling_price' => 10000, 'sku' => ' abc '];
        $this->post('/spare-parts', $data)->assertRedirect();
        $part = SparePart::sole();
        $this->post('/spare-parts', array_replace($data, ['sku' => 'AbC']))->assertSessionHasErrors('sku');
        $this->put('/spare-parts/'.$part->id, array_replace($data, ['version' => 1, 'unit' => 'botol']))->assertSessionHasErrors('unit');
        $this->assertSame('pcs', $part->fresh()->unit);
        foreach ([1, 2] as $unused) {
            $this->post('/spare-parts', array_replace($data, ['sku' => '']))->assertRedirect();
        }
        $foreign = WorkshopMembership::factory()->create(['role' => 'owner']);
        $this->actingAs($foreign->user)->withSession(['active_workshop_id' => $foreign->workshop_id])->post('/spare-parts', array_replace($data, ['workshop_context' => $foreign->workshop_id]))->assertRedirect();
        $this->assertDatabaseCount('spare_parts', 4);
        $this->post('/spare-parts', array_replace($data, ['workshop_context' => $foreign->workshop_id, 'sku' => '0']))->assertRedirect();
        $this->assertDatabaseHas('spare_parts', ['workshop_id' => $foreign->workshop_id, 'sku' => '0']);
    }

    public function test_tenant_ids_context_versions_validation_search_and_pagination_are_enforced(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $foreignOwner = WorkshopMembership::factory()->create(['role' => 'owner']);
        $foreign = $this->part($foreignOwner, ['name' => 'FOREIGN ITEM']);
        $this->get('/spare-parts/'.$foreign->id)->assertNotFound();
        $this->get('/spare-parts/999999999999999999999999')->assertNotFound();
        $this->post('/services', ['workshop_context' => $owner->workshop_id, 'name' => ' ', 'default_price' => -1])->assertSessionHasErrors(['name', 'default_price']);
        $this->post('/spare-parts', ['workshop_context' => $owner->workshop_id, 'name' => 'New', 'selling_price' => 1.5, 'unit' => 'pcs', 'minimum_stock' => -1])->assertSessionHasErrors(['selling_price', 'minimum_stock']);
        $data = ['workshop_context' => $owner->workshop_id, 'version' => 1, 'name' => 'Changed', 'unit' => 'botol', 'selling_price' => 0];
        $this->restock($owner, $part);
        $this->put('/spare-parts/'.$part->id, $data)->assertStatus(409);
        $this->put('/spare-parts/'.$part->id, array_replace($data, ['version' => 2, 'workshop_context' => $foreignOwner->workshop_id]))->assertStatus(409);
        $this->get('/spare-parts?q=%25')->assertDontSee('Oli Botol');
        $this->get('/spare-parts?low=1')->assertDontSee('Oli Botol');
        $this->get('/spare-parts?q=Oli')->assertSee('Oli Botol')->assertDontSee('FOREIGN ITEM');
        for ($i = 0; $i < 21; $i++) {
            $this->part($owner, ['name' => 'Page '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }
        $this->get('/spare-parts?page=2')->assertOk()->assertSee('Page 20');
        $owner->is_active = false;
        $owner->save();
        $this->get('/spare-parts')->assertRedirect(route('workshops.select'));
    }
}
