<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;



/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Heirachies>
 */
class HierarchyFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {  return [
        'rank' => fake()->numberBetween(0,7),
        'position_name' => fake()->name,
        'position_slang' => fake()->name,
        // 'person_name' => fake()->name,
        'hierarchy_tree_id' => fake()->numberBetween(0,10),
        // 'user_id'=>fake()->numberBetween(0,20),
    ];
}


}
