<?php

namespace Tests\Unit;

use App\Services\AiVerifier;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AiVerifierTest extends TestCase
{
    public function test_it_parses_strict_ai_json(): void
    {
        $verifier = new AiVerifier(new Factory);

        $result = $verifier->parseStrictJson('{"is_match":true,"selected_video_id":"abc123","accuracy":92,"reason":"Clear product match"}');

        $this->assertTrue($result->isMatch);
        $this->assertSame('abc123', $result->selectedVideoId);
        $this->assertSame(92, $result->accuracy);
    }

    public function test_it_rejects_missing_ai_json_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AiVerifier(new Factory))->parseStrictJson('{"is_match":true}');
    }
}
