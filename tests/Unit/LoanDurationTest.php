<?php

namespace Tests\Unit;

use App\Enums\LoanDuration;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pulang_sekolah_sets_due_at_to_1600_same_day(): void
    {
        $borrowedAt = now()->startOfDay()->setTime(9, 0);

        $dueAt = Loan::calculateDueAt('pulang_sekolah', $borrowedAt);

        $this->assertSame(16, $dueAt->hour);
        $this->assertSame(0, $dueAt->minute);
        $this->assertSame($borrowedAt->day, $dueAt->day);
    }

    public function test_one_day_duration_adds_one_day_to_due_date(): void
    {
        $borrowedAt = now()->startOfDay()->setTime(9, 0);

        $dueAt = Loan::calculateDueAt('1_hari', $borrowedAt);

        $this->assertSame($borrowedAt->copy()->addDay()->day, $dueAt->day);
        $this->assertSame(23, $dueAt->hour); // endOfDay
    }

    public function test_one_week_duration_adds_seven_days(): void
    {
        $borrowedAt = now()->startOfDay();

        $dueAt = Loan::calculateDueAt('1_minggu', $borrowedAt);

        $this->assertSame($borrowedAt->copy()->addWeek()->day, $dueAt->day);
    }

    public function test_invalid_duration_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Loan::calculateDueAt('tidak-valid', now());
    }

    public function test_is_overdue_returns_true_when_past_due_and_still_active(): void
    {
        $loan = Loan::factory()->create([
            'status' => 'aktif',
            'due_at' => now()->subHour(),
        ]);

        $this->assertTrue($loan->isOverdue());
    }

    public function test_is_overdue_returns_false_when_returned(): void
    {
        $loan = Loan::factory()->returned()->create([
            'due_at' => now()->subHour(),
        ]);

        $this->assertFalse($loan->isOverdue());
    }

    public function test_is_overdue_returns_false_when_not_past_due(): void
    {
        $loan = Loan::factory()->create([
            'status' => 'aktif',
            'due_at' => now()->addDay(),
        ]);

        $this->assertFalse($loan->isOverdue());
    }

    public function test_all_duration_values_have_label(): void
    {
        foreach (LoanDuration::cases() as $duration) {
            $this->assertNotEmpty($duration->label());
            $this->assertGreaterThanOrEqual(0, $duration->days());
        }
    }
}
