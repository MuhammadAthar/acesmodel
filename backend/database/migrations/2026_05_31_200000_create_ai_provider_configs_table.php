<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_configs', function (Blueprint $table) {
            $table->id();
            // task the model handles
            $table->string('task_type'); // image_generation|video_generation|ad_creative|copy_generation|social_post|seo_content
            // subscription tier this config applies to
            $table->string('tier');      // free|freemium|premium|pro_premium
            $table->string('provider');  // Replicate|OpenAI|Anthropic|Runway|Stability AI|Google|Custom
            $table->string('model_id');  // actual API identifier e.g. "black-forest-labs/flux-schnell"
            $table->string('model_name'); // human-readable name e.g. "FLUX.1 Schnell"
            $table->text('description')->nullable();
            $table->decimal('cost_per_use', 10, 6)->nullable(); // USD cost per call
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // provider-specific extra config
            $table->timestamps();

            $table->unique(['task_type', 'tier']); // one active config per task+tier
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_configs');
    }
};
