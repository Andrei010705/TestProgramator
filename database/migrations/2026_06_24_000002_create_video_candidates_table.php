<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('youtube_video_id')->index();
            $table->string('title');
            $table->string('channel_title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_ai_selected')->default(false);
            $table->boolean('is_match')->nullable();
            $table->unsignedTinyInteger('accuracy')->nullable();
            $table->text('ai_reason')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'youtube_video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_candidates');
    }
};
