<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'item_code' => 'ITM-' . strtoupper(uniqid()),
            'epc_tag' => null,
            'name' => $this->faker->words(3, true),
            'type' => ItemType::ASET,
            'stock' => 1,
            'status' => 'tersedia',
            'location' => $this->faker->randomElement(['Lab 1 - Rak 1', 'Lab 1 - Rak 2', 'Lab 2 - Rak 1']),
        ];
    }

    public function consumable(): static
    {
        return $this->state(fn () => [
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => $this->faker->numberBetween(5, 50),
        ]);
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => 'tersedia']);
    }

    public function borrowed(): static
    {
        return $this->state(fn () => ['status' => 'dipinjam']);
    }

    public function underMaintenance(): static
    {
        return $this->state(fn () => ['status' => 'under_maintenance']);
    }
}
