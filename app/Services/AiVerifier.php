<?php

namespace App\Services;

use App\Contracts\AiVerifierContract;
use App\DTO\AiVerificationResult;
use App\Models\Product;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class AiVerifier implements AiVerifierContract
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function verify(Product $product, array $candidates): AiVerificationResult
    {
        $apiKey = config('services.ai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Missing AI_API_KEY.');
        }

        // TODO: Complete the real OpenAI request body, retries, cache, rate limiting, and error mapping.
        $response = $this->http->withToken($apiKey)->post(config('services.ai.endpoint'), [
            'model' => config('services.ai.model'),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'Return strict JSON with is_match, selected_video_id, accuracy, reason.'],
                ['role' => 'user', 'content' => json_encode([
                    'product' => $product->only(['sku', 'name', 'brand', 'category', 'description']),
                    'candidates' => $candidates,
                ], JSON_THROW_ON_ERROR)],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('AI verification failed.');
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content)) {
            throw new RuntimeException('AI response did not include JSON content.');
        }

        return $this->parseStrictJson($content);
    }

    public function parseStrictJson(string $json): AiVerificationResult
    {
        return AiVerificationResult::fromStrictJson($json);
    }
}
