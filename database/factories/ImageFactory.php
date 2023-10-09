<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'small' => 'https://picsum.photos/100',
            'medium' => 'https://picsum.photos/200',
            'large' => 'https://picsum.photos/500',
            'full' => 'https://picsum.photos/500',
            'user_id' => 1
        ];
    }
}
