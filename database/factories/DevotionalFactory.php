<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;



/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Devotional>
 */
class DevotionalFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'title' => fake()->name,
        'opening_prayer' => fake()->paragraph,
        'closing_prayer' => fake()->paragraph,
        'body' => fake()->text,
        'memory_verse' => 'John 3:16',
        'day' => fake()->dateTime,
        'poster_id' => fake()->numberBetween(1,10),
        'poster_type' => 'user'
    ];
}


public function poster($type = 'user', $id = 1): static
{
    return $this->state(fn (array $attributes) => [
        'poster_id' => $type,
        'poster_type' => $id,
    ]);
}
}