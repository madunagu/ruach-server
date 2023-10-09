<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;



/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comment' => fake()->sentence,
            'user_id' => rand(1,50) ,
            'commentable_id' => rand(1,50),
            'commentable_type' => 'video'
        ];
    }

    public function commentable($type = 'video', $id = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => $type,
            'commentable_id' => $id,
        ]);
    }


    public function poster($type = 'user', $id = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'poster_id' => $type,
            'poster_type' => $id,
        ]);
    }
}
