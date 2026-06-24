<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductQueryTest extends TestCase
{
    public function test_search_scope_adds_expected_columns_to_query(): void
    {
        $sql = Product::query()->search('sony')->toSql();

        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('sku', $sql);
        $this->assertStringContainsString('brand', $sql);
        $this->assertStringContainsString('category', $sql);
    }
}
