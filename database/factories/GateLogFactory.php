<?php

namespace Database\Factories;

use App\Models\GateLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GateLog>
 */
class GateLogFactory extends Factory
{
    protected $model = GateLog::class;

    public function definition(): array
    {
        return [
            'epc_tag_scanned' => fake()->unique()->bothify('E280##############'),
            'action_status' => fake()->randomElement(['allowed', 'denied']),
            'capture_image_path' => null,
        ];
    }

    public function allowed(): static
    {
        return $this->state(fn () => ['action_status' => 'allowed']);
    }

    public function denied(): static
    {
        return $this->state(fn () => ['action_status' => 'denied']);
    }
}
