<?php

namespace Tests\Feature\Console;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckOverdueLoansTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_loan_gets_notified_and_marked(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'phone' => '081234567890',
        ]);
        $item = Item::factory()->create(['name' => 'Mikrotik RB951']);
        $loan = Loan::factory()->overdue()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
        ]);

        $this->artisan('loans:check-overdue')
            ->expectsOutputToContain('Notifikasi terkirim')
            ->assertExitCode(0);

        $this->assertNotNull($loan->fresh()->overdue_notified_at);
    }

    public function test_loan_without_phone_is_skipped(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'phone' => null,
        ]);
        $item = Item::factory()->create();
        $loan = Loan::factory()->overdue()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
        ]);

        $this->artisan('loans:check-overdue')
            ->expectsOutputToContain('dilewati')
            ->assertExitCode(0);

        $this->assertNull($loan->fresh()->overdue_notified_at);
    }

    public function test_already_notified_loan_is_not_sent_again(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'phone' => '081234567890',
        ]);
        $item = Item::factory()->create();
        Loan::factory()->notified()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
        ]);

        $this->artisan('loans:check-overdue')
            ->expectsOutputToContain('Tidak ada peminjaman overdue')
            ->assertExitCode(0);
    }

    public function test_non_overdue_loan_is_not_notified(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'phone' => '081234567890',
        ]);
        $item = Item::factory()->create();
        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'due_at' => now()->addDay(), // belum jatuh tempo
        ]);

        $this->artisan('loans:check-overdue')
            ->expectsOutputToContain('Tidak ada peminjaman overdue')
            ->assertExitCode(0);

        $this->assertNull($loan->fresh()->overdue_notified_at);
    }

    public function test_returned_loan_is_not_notified_even_if_due_at_passed(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'phone' => '081234567890',
        ]);
        $item = Item::factory()->create();
        $loan = Loan::factory()->returned()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'due_at' => now()->subDay(),
        ]);

        $this->artisan('loans:check-overdue')
            ->expectsOutputToContain('Tidak ada peminjaman overdue')
            ->assertExitCode(0);

        $this->assertNull($loan->fresh()->overdue_notified_at);
    }
}
