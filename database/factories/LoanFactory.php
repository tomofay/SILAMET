<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $duration = fake()->randomElement(['pulang_sekolah', '1_hari', '2_hari', '3_hari', '1_minggu']);
        $borrowedAt = now();

        return [
            'user_id' => User::factory(),
            'item_id' => Item::factory(),
            'status' => 'aktif',
            'condition_in' => null,
            'usage_duration' => $duration,
            'borrowed_at' => $borrowedAt,
            'due_at' => Loan::calculateDueAt($duration, $borrowedAt),
            'returned_at' => null,
            'overdue_notified_at' => null,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'status' => 'selesai',
            'condition_in' => 'bagus',
            'returned_at' => now(),
        ]);
    }

    public function returnedDamaged(): static
    {
        return $this->state(fn () => [
            'status' => 'selesai',
            'condition_in' => 'rusak',
            'returned_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_at' => now()->subDay(),
            'overdue_notified_at' => null,
        ]);
    }

    public function notified(): static
    {
        return $this->state(fn () => [
            'due_at' => now()->subDay(),
            'overdue_notified_at' => now(),
        ]);
    }
}
