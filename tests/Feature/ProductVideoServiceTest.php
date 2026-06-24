<?php

namespace Tests\Feature;

use App\Contracts\AiVerifierContract;
use App\Contracts\YouTubeClientContract;
use App\DTO\AiVerificationResult;
use App\DTO\YouTubeCandidateData;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVideoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_update_persists_selected_video_and_candidate_verdict(): void
    {
        $product = Product::factory()->create(['name' => 'Camera X', 'brand' => 'Acme']);

        $this->app->bind(YouTubeClientContract::class, fn () => new class implements YouTubeClientContract {
            public function searchForProduct(string $query, int $limit = 5): array
            {
                return [new YouTubeCandidateData('vid123', 'Acme Camera X demo')];
            }
        });

        $this->app->bind(AiVerifierContract::class, fn () => new class implements AiVerifierContract {
            public function verify(Product $product, array $candidates): AiVerificationResult
            {
                return new AiVerificationResult(true, 'vid123', 95, 'Model and brand match.');
            }
        });

        $this->post(route('products.search-youtube', $product))->assertRedirect();

        $product->refresh();

        $this->assertSame('vid123', $product->selected_youtube_video_id);
        $this->assertSame('https://www.youtube.com/watch?v=vid123', $product->video_url);
        $this->assertDatabaseHas('video_candidates', [
            'product_id' => $product->id,
            'youtube_video_id' => 'vid123',
            'is_ai_selected' => true,
            'accuracy' => 95,
        ]);
    }
}
