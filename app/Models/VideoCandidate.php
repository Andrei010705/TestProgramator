<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'youtube_video_id',
        'title',
        'channel_title',
        'description',
        'published_at',
        'thumbnail_url',
        'is_ai_selected',
        'is_match',
        'accuracy',
        'ai_reason',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_ai_selected' => 'boolean',
        'is_match' => 'boolean',
        'accuracy' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
