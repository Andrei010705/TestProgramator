<?php

namespace App\Services;

use App\Contracts\AiVerifierContract;
use App\Contracts\YouTubeClientContract;
use App\DTO\YouTubeCandidateData;
use App\Models\Product;
use App\Models\VideoCandidate;
use Illuminate\Support\Facades\DB;

class ProductVideoService
{
    public function __construct(
        private readonly YouTubeClientContract $youTubeClient,
        private readonly AiVerifierContract $aiVerifier,
    ) {
    }

    public function searchAndVerify(Product $product): Product
    {
        $query = $this->buildSearchQuery($product);
        $candidates = $this->youTubeClient->searchForProduct($query);
        $aiResult = $this->aiVerifier->verify($product, $candidates);

        return DB::transaction(function () use ($product, $candidates, $aiResult): Product {
            foreach ($candidates as $candidate) {
                /** @var YouTubeCandidateData $candidate */
                $product->videoCandidates()->updateOrCreate(
                    ['youtube_video_id' => $candidate->videoId],
                    $candidate->toPersistenceArray() + [
                        'is_ai_selected' => $candidate->videoId === $aiResult->selectedVideoId,
                        'is_match' => $candidate->videoId === $aiResult->selectedVideoId ? $aiResult->isMatch : null,
                        'accuracy' => $candidate->videoId === $aiResult->selectedVideoId ? $aiResult->accuracy : null,
                        'ai_reason' => $candidate->videoId === $aiResult->selectedVideoId ? $aiResult->reason : null,
                    ],
                );
            }

            if ($aiResult->isMatch && $aiResult->selectedVideoId) {
                $product->forceFill([
                    'selected_youtube_video_id' => $aiResult->selectedVideoId,
                    'video_url' => 'https://www.youtube.com/watch?v='.$aiResult->selectedVideoId,
                    'ai_accuracy' => $aiResult->accuracy,
                    'ai_reason' => $aiResult->reason,
                    'video_verified_at' => now(),
                ])->save();
            }

            return $product->refresh()->load('videoCandidates');
        });
    }

    public function buildSearchQuery(Product $product): string
    {
        return collect([$product->brand, $product->name, $product->sku])
            ->filter()
            ->join(' ');
    }
}
