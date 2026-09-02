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
}
