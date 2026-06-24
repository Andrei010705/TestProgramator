<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory()->count(10)->create();

        // TODO: Replace this placeholder with CSV/XLS import for the real product source.
    }
}
