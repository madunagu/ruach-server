<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Storage;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AudioPost>
 */
class AudioPostFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence,
            'src_url' => Storage::url('audio/Hillsong-Touch-Of-Heaven.mp3'),
            'full_text' => fake()->paragraph,
            'description' => fake()->sentence,
            'user_id' => rand(1, 20),
            'poster_id' => rand(1, 20),
            'poster_type' => 'user',
            'size' => fake()->numberBetween(0, 200000),
            'length' => fake()->numberBetween(0, 660),
            'language' => fake()->languageCode,
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
