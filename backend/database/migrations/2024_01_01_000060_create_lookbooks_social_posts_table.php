<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('draft'); // draft|generating|ready
            $table->json('pages')->nullable();          // ordered array of asset IDs + layout
            $table->string('pdf_path')->nullable();
            $table->string('web_slug')->unique()->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform'); // instagram|tiktok|facebook|pinterest
            $table->string('post_type')->nullable(); // post|carousel|story|reel|pin|ad
            $table->longText('caption')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('media_asset_ids')->nullable(); // array of generated_asset IDs
            $table->string('status')->default('draft'); // draft|scheduled|published
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('lookbooks');
    }
};
