<?php

namespace Tests\Unit;

use App\Services\YouTubeClient;
use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;

class YouTubeClientTest extends TestCase
{
    public function test_it_parses_youtube_search_response_into_candidates(): void
    {
        $client = new YouTubeClient(new Factory);

        $candidates = $client->parseSearchResponse([
            'items' => [[
                'id' => ['videoId' => 'abc123'],
                'snippet' => [
                    'title' => 'Product review',
                    'channelTitle' => 'Demo Channel',
                    'description' => 'A useful product video',
                    'publishedAt' => '2026-01-01T00:00:00Z',
                    'thumbnails' => ['default' => ['url' => 'https://example.test/thumb.jpg']],
                ],
            ]],
        ]);

        $this->assertCount(1, $candidates);
        $this->assertSame('abc123', $candidates[0]->videoId);
        $this->assertSame('Product review', $candidates[0]->title);
    }
}
