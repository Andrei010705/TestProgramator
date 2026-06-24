<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->nullable()->index();
            $table->string('name')->index();
            $table->string('brand')->nullable()->index();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('video_url')->nullable();
            $table->string('selected_youtube_video_id')->nullable();
            $table->unsignedTinyInteger('ai_accuracy')->nullable();
            $table->text('ai_reason')->nullable();
            $table->timestamp('video_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
