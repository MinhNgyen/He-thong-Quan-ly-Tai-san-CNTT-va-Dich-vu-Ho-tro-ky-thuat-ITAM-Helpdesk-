<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->bothify('EMP####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'role_id' => Role::query()->firstOrCreate(['name' => 'Employee'], ['description' => 'Nhan vien'])->id,
            'department_id' => Department::query()->firstOrCreate(['name' => 'General'], ['description' => 'Phong ban chung'])->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::query()->firstOrCreate(['name' => 'Admin'], ['description' => 'Quan tri he thong'])->id,
        ]);
    }

    public function technician(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::query()->firstOrCreate(['name' => 'Technician'], ['description' => 'Nhan vien ky thuat'])->id,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
