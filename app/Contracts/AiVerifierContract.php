<?php

namespace App\Contracts;

use App\DTO\AiVerificationResult;
use App\Models\Product;

interface AiVerifierContract
{
    public function verify(Product $product, array $candidates): AiVerificationResult;
}
