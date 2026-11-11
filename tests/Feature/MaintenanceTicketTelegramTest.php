<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaintenanceTicketTelegramTest extends TestCase
{
    use RefreshDatabase;

    private function enableFakeTelegram(bool $notifyResolved = false): void
    {
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'test-ticket-token');
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.admin_chat_id', '123456789');
        Config::set('services.telegram.notify_ticket_resolved', $notifyResolved);
    }

    public function test_kiosk_damaged_return_triggers_telegram_alert(): void
    {
        $this->enableFakeTelegram();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 51]], 200),
        ]);

        $student = User::factory()->siswa()->create(['nis_nip' => '10229901', 'name' => 'Rafi']);
        $item = Item::factory()->borrowed()->create(['item_code' => 'SLM-DMG-01', 'name' => 'Crimping Tool']);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => 'aktif',
        ]);

        $this->postJson('/api/v1/loans/return-kiosk', [
            'nis' => '10229901',
            'items' => [
                ['item_code' => 'SLM-DMG-01', 'condition_in' => 'rusak', 'issue_description' => 'Per penjepit patah'],
            ],
        ])->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_contains((string) $request['text'], 'LAPORAN KERUSAKAN ALAT BARU')
                && str_contains((string) $request['text'], 'Per penjepit patah');
        });
    }

    public function test_web_ticket_store_and_resolved_alert(): void
    {
        $this->enableFakeTelegram(true);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 52]], 200),
        ]);

        $teknisi = User::factory()->teknisi()->create();
        $item = Item::factory()->available()->create(['name' => 'LAN Tester', 'item_code' => 'SLM-LT-02']);

        $this->actingAs($teknisi)->post(route('maintenance.store'), [
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Layar LED indikator mati',
            'status' => 'open',
        ])->assertRedirect(route('maintenance.index'));

        $ticket = MaintenanceTicket::firstOrFail();

        $this->actingAs($teknisi)->put(route('maintenance.update', $ticket->id), [
            'status' => 'resolved',
        ])->assertRedirect(route('maintenance.index'));

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'ALAT SELESAI DIPERBAIKI'));
    }
}

