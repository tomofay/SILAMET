<?php

namespace Tests\Feature\Console;

use App\Models\Item;
use App\Models\PreventiveSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckDueMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function enableFakeTelegram(): void
    {
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'test-maint-token');
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.admin_chat_id', '123456789');
    }

    public function test_notifies_approaching_maintenance_across_stages_without_duplicates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta'));
        $this->enableFakeTelegram();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 31]], 200),
        ]);

        $item = Item::factory()->create([
            'name' => 'Switch Manageable Cisco',
            'item_code' => 'SLM-SW-01',
            'location' => 'Lab SIJA - Rak 2',
        ]);

        $schedule = PreventiveSchedule::factory()->create([
            'item_id' => $item->id,
            'title' => 'Pembersihan Fan & Cek Port',
            'next_due_date' => '2026-11-17', // H-7
            'status' => 'scheduled',
            'last_notified_stage' => null,
        ]);

        // Hari H-7 -> harus kirim 1x (Group + Admin = 2 HTTP calls)
        $this->artisan('maintenance:check-due')->assertExitCode(0);
        $this->assertSame('h7', $schedule->fresh()->last_notified_stage);
        Http::assertSentCount(2);

        // Dipanggil lagi di hari yang sama -> tidak boleh kirim ulang
        $this->artisan('maintenance:check-due')->assertExitCode(0);
        Http::assertSentCount(2);

        // Maju ke H-3 (14 Nov) -> kirim stage h3
        Carbon::setTestNow(Carbon::parse('2026-11-14 08:00:00', 'Asia/Jakarta'));
        $this->artisan('maintenance:check-due')->assertExitCode(0);
        $this->assertSame('h3', $schedule->fresh()->last_notified_stage);
        Http::assertSentCount(4);

        // Maju ke Hari-H (17 Nov) -> kirim stage h0
        Carbon::setTestNow(Carbon::parse('2026-11-17 08:00:00', 'Asia/Jakarta'));
        $this->artisan('maintenance:check-due')->assertExitCode(0);
        $this->assertSame('h0', $schedule->fresh()->last_notified_stage);
        Http::assertSentCount(6);

        Carbon::setTestNow();
    }

    public function test_ignores_paused_and_far_future_schedules(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta'));
        $this->enableFakeTelegram();
        Http::fake();

        PreventiveSchedule::factory()->dueInDays(20)->create();
        PreventiveSchedule::factory()->dueInDays(2)->paused()->create();

        $this->artisan('maintenance:check-due')->assertExitCode(0);

        Http::assertNothingSent();
        Carbon::setTestNow();
    }
}

