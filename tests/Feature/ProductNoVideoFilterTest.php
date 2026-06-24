<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductNoVideoFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_video_filter_shows_only_products_without_video(): void
    {
        Product::factory()->create(['name' => 'Needs Video']);
        Product::factory()->withVideo()->create(['name' => 'Already Has Video']);

        $this->get(route('products.index', ['without_video' => 1]))
            ->assertOk()
            ->assertSee('Needs Video')
            ->assertDontSee('Already Has Video');
    }
}
