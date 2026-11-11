<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Services\GateValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_borrowed_item(): void
    {
        $item = Item::factory()->borrowed()->create([
            'epc_tag' => 'EPC-DIPINJAM',
        ]);

        $result = app(GateValidationService::class)->checkTag('EPC-DIPINJAM');

        $this->assertSame('allowed', $result['status']);
        $this->assertSame($item->name, $result['item_name']);
    }

    public function test_denies_available_item(): void
    {
        Item::factory()->available()->create([
            'epc_tag' => 'EPC-TERSEDIA',
            'name' => 'Router Cisco',
        ]);

        $result = app(GateValidationService::class)->checkTag('EPC-TERSEDIA');

        $this->assertSame('denied', $result['status']);
        $this->assertStringContainsString('Router Cisco', $result['reason']);
    }

    public function test_denies_under_maintenance_item(): void
    {
        Item::factory()->underMaintenance()->create([
            'epc_tag' => 'EPC-SERVICE',
        ]);

        $result = app(GateValidationService::class)->checkTag('EPC-SERVICE');

        $this->assertSame('denied', $result['status']);
    }

    public function test_denies_unknown_epc(): void
    {
        $result = app(GateValidationService::class)->checkTag('EPC-TIDAK-ADA');

        $this->assertSame('denied', $result['status']);
        $this->assertStringContainsString('tidak ditemukan', $result['reason']);
    }

    public function test_batch_check_tags_validates_mixed_epcs_in_single_lookup(): void
    {
        Item::factory()->borrowed()->create(['epc_tag' => 'EPC-BATCH-ALLOW', 'name' => 'Tang Krimping LAN']);
        Item::factory()->available()->create(['epc_tag' => 'EPC-BATCH-DENY-1', 'name' => 'Switch 24 Port']);
        Item::factory()->underMaintenance()->create(['epc_tag' => '0700EE00BATCH2', 'name' => 'Fusion Splicer']);

        $results = app(GateValidationService::class)->checkTags([
            'EPC-BATCH-ALLOW',
            'EPC-BATCH-DENY-1',
            'BATCH2',
            'EPC-UNKNOWN-99',
        ]);

        $this->assertCount(4, $results);
        $this->assertSame('allowed', $results['EPC-BATCH-ALLOW']['status']);
        $this->assertSame('Tang Krimping LAN', $results['EPC-BATCH-ALLOW']['item_name']);
        $this->assertSame('denied', $results['EPC-BATCH-DENY-1']['status']);
        $this->assertSame('denied', $results['BATCH2']['status']);
        $this->assertSame('Fusion Splicer', $results['BATCH2']['item_name']);
        $this->assertSame('denied', $results['EPC-UNKNOWN-99']['status']);
        $this->assertNull($results['EPC-UNKNOWN-99']['item_id']);
    }
}
