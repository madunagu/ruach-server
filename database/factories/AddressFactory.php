<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'address1' => fake()->streetAddress,
      'address2' => fake()->streetAddress,
      'user_id' => 1,
      'country' => fake()->country,
      'state' => fake()->state,
      'city' => fake()->city,
      'postal_code' => fake()->postcode,
      'name' => fake()->name,
      'latitude' => fake()->latitude(-90, 90),
      'longitude' => fake()->longitude(-180, 180),
    ];
  }




  public function pareseable(): static
  {
    return $this->state(fn (array $attributes) => [
      'parseable' => 1,
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
