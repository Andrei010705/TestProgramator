<?php

namespace App\Contracts;

interface YouTubeClientContract
{
    public function searchForProduct(string $query, int $limit = 5): array;
}
