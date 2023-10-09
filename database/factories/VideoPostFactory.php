<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VideoPost>
 */
class VideoPostFactory extends Factory
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
            'src_url' => Storage::url('video/videoplayback.mp4'),
            'full_text' => fake()->paragraph,
            'description' => fake()->sentence,
            'author_id' => 1,
            'uploader_id' => rand(1, 20),
            'size' => fake()->numberBetween(0, 200000),
            'length' => fake()->numberBetween(0, 200000),
            'language' => fake()->languageCode,
        ];
    }
}
