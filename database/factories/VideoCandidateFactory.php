<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoCandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'youtube_video_id' => fake()->bothify('???????????'),
            'title' => fake()->sentence(4),
            'channel_title' => fake()->company(),
            'description' => fake()->sentence(),
            'published_at' => now()->subDays(fake()->numberBetween(1, 1000)),
            'thumbnail_url' => fake()->imageUrl(120, 90),
        ];
    }
}
