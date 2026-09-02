<?php

namespace Tests\Feature\Api;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_available_items(): void
    {
        Item::factory()->available()->create();
        Item::factory()->borrowed()->create();
        Item::factory()->underMaintenance()->create();

        $response = $this->getJson('/api/v1/items');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->assertSame('tersedia', $response->json('data.0.status'));
    }

    public function test_item_detail_returns_relationships(): void
    {
        $item = Item::factory()->create();

        $response = $this->getJson("/api/v1/items/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('data.item_code', $item->item_code)
            ->assertJsonStructure([
                'success',
                'data' => ['loans', 'tickets'],
            ]);
    }

    public function test_item_detail_not_found(): void
    {
        $response = $this->getJson('/api/v1/items/99999');

        $response->assertStatus(404);
    }

    public function test_item_code_is_unique(): void
    {
        $existing = Item::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Item::factory()->create(['item_code' => $existing->item_code]);
    }
}
