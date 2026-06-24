<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'brand',
        'category',
        'description',
        'price',
        'video_url',
        'selected_youtube_video_id',
        'ai_accuracy',
        'ai_reason',
        'video_verified_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'video_verified_at' => 'datetime',
    ];

    public function videoCandidates(): HasMany
    {
        return $this->hasMany(VideoCandidate::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        });
    }

    public function scopeWithoutVideo(Builder $query, bool $withoutVideo): Builder
    {
        return $query->when($withoutVideo, function (Builder $query): void {
            $query->whereNull('video_url')
                ->whereNull('selected_youtube_video_id');
        });
    }
}
