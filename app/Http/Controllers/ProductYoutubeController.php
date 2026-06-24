<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductVideoService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ProductYoutubeController
{
    public function __invoke(Product $product, ProductVideoService $service): RedirectResponse
    {
        try {
            $service->searchAndVerify($product);

            return back()->with('status', 'YouTube candidates searched and AI verdict saved.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'youtube' => 'Video search could not be completed yet. Check API keys and service logs.',
            ]);
        }
    }
}
