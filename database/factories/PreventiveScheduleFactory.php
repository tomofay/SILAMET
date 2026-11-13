<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PreventiveSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreventiveSchedule>
 */
class PreventiveScheduleFactory extends Factory
{
    protected $model = PreventiveSchedule::class;

    public function definition(): array
    {
        $lastServiced = today()->subMonths(2);
        $intervalMonths = 3;

        return [
            'item_id' => Item::factory(),
            'title' => 'Pembersihan & Kalibrasi Berkala',
            'interval_months' => $intervalMonths,
            'last_serviced_at' => $lastServiced,
            'maintenance_date' => $lastServiced,
            'quantity' => 1,
            'safe_quantity' => 1,
            'damaged_quantity' => 0,
            'next_due_date' => PreventiveSchedule::calculateNextDueDate($lastServiced, $intervalMonths),
            'notes' => 'Cek konektor, kipas pendingin, dan fungsi port.',
            'status' => 'scheduled',
            'workflow_stage' => 'scheduled',
            'last_notified_stage' => null,
            'last_notified_at' => null,
            'created_by' => User::factory()->teknisi(),
        ];
    }

    public function dueInDays(int $days): static
    {
        return $this->state(fn () => [
            'next_due_date' => today()->addDays($days),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => [
            'status' => 'paused',
        ]);
    }
}
