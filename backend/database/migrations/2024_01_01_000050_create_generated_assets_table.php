<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('garment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained()->nullOnDelete();
            // Asset classification
            $table->string('asset_type');  // photo|video|ad|caption|description|hashtags|lookbook_page|social_post
            $table->string('asset_subtype')->nullable(); // studio|lifestyle|editorial|reel|tiktok|meta_ad|...
            $table->string('platform')->nullable(); // instagram|tiktok|facebook|pinterest|shopify|amazon
            // Content
            $table->string('file_path')->nullable();     // for images/videos
            $table->longText('content')->nullable();     // for text assets
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            // Generation metadata
            $table->string('ai_provider')->nullable();   // replicate|openai|gemini
            $table->string('ai_model_name')->nullable(); // flux-kontext|gpt-image-1|...
            $table->json('generation_params')->nullable();
            $table->string('status')->default('pending'); // pending|generating|ready|failed
            $table->text('error_message')->nullable();
            $table->string('replicate_prediction_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_assets');
    }
};
