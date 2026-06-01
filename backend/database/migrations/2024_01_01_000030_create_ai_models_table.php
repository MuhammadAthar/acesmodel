<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Custom AI model personas created by users
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('ethnicity')->nullable();   // pakistani|arab|turkish|european|...
            $table->string('gender')->nullable();      // male|female|non-binary
            $table->string('age_range')->nullable();   // 18-25|25-35|35-50|50+
            $table->string('body_type')->nullable();   // regular|plus-size|petite|athletic
            $table->string('hair')->nullable();
            $table->string('skin_tone')->nullable();
            $table->json('style_tags')->nullable();
            $table->string('preview_image')->nullable();
            $table->string('replicate_model_ref')->nullable(); // lora/checkpoint reference
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
