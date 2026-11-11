<?php

namespace Tests\Feature\Console;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckOverdueLoansTest extends TestCase
{
    use RefreshDatabase;

    private function enableFakeTelegram(int $cooldownHours = 4): void
    {
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'test-overdue-token');
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.admin_chat_id', '123456789');
        Config::set('services.telegram.overdue_reminder_hours', $cooldownHours);
    }

    public function test_sends_overdue_alert_with_exact_lateness_duration_and_updates_tracking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 19:15:00', 'Asia/Jakarta'));
        $this->enableFakeTelegram(4);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 11]], 200),
        ]);

        $student = User::factory()->create([
            'name' => 'Andi Pratama',
            'nis_nip' => '10220011',
            'phone' => '08123456789',
            'telegram_chat_id' => '99887766',
        ]);
        $item = Item::factory()->borrowed()->create([
            'name' => 'Tang Krimping RJ45',
            'item_code' => 'SLM-TK-09',
        ]);

        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
            'due_at' => Carbon::parse('2026-11-10 16:00:00', 'Asia/Jakarta'), // 3 jam 15 menit terlambat
            'overdue_notified_at' => null,
            'overdue_notified_count' => 0,
        ]);

        $this->artisan('loans:check-overdue')->assertExitCode(0);

        $loan->refresh();
        $this->assertNotNull($loan->overdue_notified_at);
        $this->assertSame(1, $loan->overdue_notified_count);

        // Group + Admin + Personal DM ke siswa = 3 pesan
        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            return str_contains((string) $request['text'], '3 jam 15 menit terlambat')
                && str_contains((string) $request['text'], 'Andi Pratama')
                && str_contains((string) $request['text'], 'SLM-TK-09');
        });

        Carbon::setTestNow();
    }

    public function test_respects_reminder_cooldown_and_resends_after_cooldown_expires(): void
    {
        $baseNow = Carbon::parse('2026-11-10 18:00:00', 'Asia/Jakarta');
        Carbon::setTestNow($baseNow);
        $this->enableFakeTelegram(4);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 21]], 200),
        ]);

        $student = User::factory()->create(['telegram_chat_id' => null]);
        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'status' => LoanStatus::AKTIF,
            'due_at' => $baseNow->copy()->subHours(2),
            'overdue_notified_at' => null,
            'overdue_notified_count' => 0,
        ]);

        // Pengingat #1 (Group + Admin = 2 HTTP calls)
        $this->artisan('loans:check-overdue')->assertExitCode(0);
        $this->assertSame(1, $loan->fresh()->overdue_notified_count);
        Http::assertSentCount(2);

        // 2 jam kemudian (masih dalam cooldown 4 jam) -> TIDAK mengirim ulang
        Carbon::setTestNow($baseNow->copy()->addHours(2));
        $this->artisan('loans:check-overdue')->assertExitCode(0);
        Http::assertSentCount(2);

        // 4 jam 5 menit kemudian (melewati cooldown 4 jam) -> mengirim pengingat #2
        Carbon::setTestNow($baseNow->copy()->addHours(4)->addMinutes(5));
        $this->artisan('loans:check-overdue')->assertExitCode(0);
        $this->assertSame(2, $loan->fresh()->overdue_notified_count);
        Http::assertSentCount(4);

        Carbon::setTestNow();
    }

    public function test_ignores_returned_or_not_yet_due_loans(): void
    {
        $this->enableFakeTelegram();
        Http::fake();

        Loan::factory()->returned()->create([
            'due_at' => now()->subDay(),
        ]);
        Loan::factory()->create([
            'status' => LoanStatus::AKTIF,
            'due_at' => now()->addHours(3),
        ]);

        $this->artisan('loans:check-overdue')->assertExitCode(0);

        Http::assertNothingSent();
    }
}

