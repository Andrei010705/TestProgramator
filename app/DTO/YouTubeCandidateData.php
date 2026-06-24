<?php

namespace App\DTO;

use Carbon\CarbonImmutable;

readonly class YouTubeCandidateData
{
    public function __construct(
        public string $videoId,
        public string $title,
        public ?string $channelTitle = null,
        public ?string $description = null,
        public ?CarbonImmutable $publishedAt = null,
        public ?string $thumbnailUrl = null,
    ) {
    }

    /** @return array<int, self> */
    public static function collectionFromApiResponse(array $payload): array
    {
        return collect($payload['items'] ?? [])
            ->map(fn (array $item) => self::fromApiItem($item))
            ->filter()
            ->values()
            ->all();
    }

    public static function fromApiItem(array $item): ?self
    {
        $videoId = $item['id']['videoId'] ?? null;

        if (! is_string($videoId) || $videoId === '') {
            return null;
        }

        $snippet = $item['snippet'] ?? [];
        $publishedAt = isset($snippet['publishedAt'])
            ? CarbonImmutable::parse($snippet['publishedAt'])
            : null;

        return new self(
            videoId: $videoId,
            title: (string) ($snippet['title'] ?? ''),
            channelTitle: $snippet['channelTitle'] ?? null,
            description: $snippet['description'] ?? null,
            publishedAt: $publishedAt,
            thumbnailUrl: $snippet['thumbnails']['default']['url']
                ?? $snippet['thumbnails']['medium']['url']
                ?? null,
        );
    }

    public function toPersistenceArray(): array
    {
        return [
            'youtube_video_id' => $this->videoId,
            'title' => $this->title,
            'channel_title' => $this->channelTitle,
            'description' => $this->description,
            'published_at' => $this->publishedAt,
            'thumbnail_url' => $this->thumbnailUrl,
        ];
    }
}
