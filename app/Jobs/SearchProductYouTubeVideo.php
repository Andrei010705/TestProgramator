<?php

namespace App\Jobs;

use App\Models\Product;

class SearchProductYouTubeVideo
{
    public function __construct(public readonly Product $product)
    {
    }

    public function handle(): void
    {
        // TODO: Move ProductVideoService::searchAndVerify() here when async queues are introduced.
    }
}
