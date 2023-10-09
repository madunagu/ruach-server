<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;



/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(5),
            'starting_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
            'ending_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
            'user_id' => rand(1, 10),
            'description' => fake()->paragraph(5, true),
            'poster_type' => 'user',
            'poster_id' => rand(1, 10),
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
