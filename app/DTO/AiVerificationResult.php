<?php

namespace App\DTO;

use InvalidArgumentException;

readonly class AiVerificationResult
{
    public function __construct(
        public bool $isMatch,
        public ?string $selectedVideoId,
        public int $accuracy,
        public string $reason,
    ) {
        if ($this->accuracy < 0 || $this->accuracy > 100) {
            throw new InvalidArgumentException('AI accuracy must be between 0 and 100.');
        }
    }

    public static function fromStrictJson(string $json): self
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $required = ['is_match', 'selected_video_id', 'accuracy', 'reason'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw new InvalidArgumentException("AI response is missing '{$key}'.");
            }
        }

        if (! is_bool($decoded['is_match'])) {
            throw new InvalidArgumentException('AI field is_match must be boolean.');
        }

        if (! is_null($decoded['selected_video_id']) && ! is_string($decoded['selected_video_id'])) {
            throw new InvalidArgumentException('AI field selected_video_id must be string or null.');
        }

        if (! is_int($decoded['accuracy'])) {
            throw new InvalidArgumentException('AI field accuracy must be integer.');
        }

        if (! is_string($decoded['reason'])) {
            throw new InvalidArgumentException('AI field reason must be string.');
        }

        return new self(
            isMatch: $decoded['is_match'],
            selectedVideoId: $decoded['selected_video_id'],
            accuracy: $decoded['accuracy'],
            reason: $decoded['reason'],
        );
    }
}
