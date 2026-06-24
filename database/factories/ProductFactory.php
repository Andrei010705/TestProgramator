<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'name' => fake()->words(3, true),
            'brand' => fake()->company(),
            'category' => fake()->word(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
        ];
    }

    public function withVideo(): self
    {
        return $this->state(fn () => [
            'video_url' => 'https://www.youtube.com/watch?v='.fake()->bothify('???????????'),
            'selected_youtube_video_id' => fake()->bothify('???????????'),
            'video_verified_at' => now(),
        ]);
    }
}
