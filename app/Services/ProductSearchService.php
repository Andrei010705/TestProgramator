<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductSearchService
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['videoCandidates' => fn ($query) => $query->latest()])
            ->search($filters['search'] ?? null)
            ->withoutVideo((bool) ($filters['without_video'] ?? false))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
