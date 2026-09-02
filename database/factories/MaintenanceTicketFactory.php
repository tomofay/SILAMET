<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTicket>
 */
class MaintenanceTicketFactory extends Factory
{
    protected $model = MaintenanceTicket::class;

    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'reported_by' => User::factory(),
            'issue_description' => fake()->sentence(8),
            'status' => 'open',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => 'in_progress']);
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['status' => 'resolved']);
    }
}
