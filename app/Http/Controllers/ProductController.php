<?php

namespace App\Http\Controllers;

use App\Services\ProductSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController
{
    public function index(Request $request, ProductSearchService $products): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'without_video' => ['nullable', 'boolean'],
        ]);

        return view('products.index', [
            'products' => $products->paginate($filters),
            'filters' => $filters,
        ]);
    }
}
