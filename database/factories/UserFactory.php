<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->numerify('08##########'),
            'nis_nip' => fake()->unique()->numerify('##########'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'siswa',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function siswa(): static
    {
        return $this->state(fn () => ['role' => 'siswa']);
    }

    public function teknisi(): static
    {
        return $this->state(fn () => ['role' => 'teknisi']);
    }

    public function kabeng(): static
    {
        return $this->state(fn () => ['role' => 'kabeng']);
    }

    public function kaprog(): static
    {
        return $this->state(fn () => ['role' => 'kaprog']);
    }
}
