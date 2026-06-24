<?php

namespace App\Services;

use App\Contracts\YouTubeClientContract;
use App\DTO\YouTubeCandidateData;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class YouTubeClient implements YouTubeClientContract
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    /** @return array<int, YouTubeCandidateData> */
    public function searchForProduct(string $query, int $limit = 5): array
    {
        $apiKey = config('services.youtube.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Missing YOUTUBE_API_KEY.');
        }

        // TODO: Add cache, rate limiting, quota handling, and richer YouTube error mapping.
        $response = $this->http->get(config('services.youtube.search_url'), [
            'key' => $apiKey,
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => $limit,
            'q' => $query,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('YouTube search failed.');
        }

        return $this->parseSearchResponse($response->json() ?? []);
    }

    /** @return array<int, YouTubeCandidateData> */
    public function parseSearchResponse(array $payload): array
    {
        return YouTubeCandidateData::collectionFromApiResponse($payload);
    }
}
