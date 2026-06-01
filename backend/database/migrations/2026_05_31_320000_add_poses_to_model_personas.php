<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add character consistency columns to model_personas
        Schema::table('model_personas', function (Blueprint $table) {
            // Random int seed passed to Flux for every pose generation — keeps the character visually consistent
            $table->unsignedInteger('character_seed')->nullable()->after('avatar_url');
            // Locked subject description used verbatim in every pose prompt
            $table->text('character_lock_prompt')->nullable()->after('character_seed');
        });

        // Poses table — multiple photos per persona, each with a different pose/angle
        Schema::create('model_persona_poses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                  ->constrained('model_personas')
                  ->cascadeOnDelete();
            $table->string('pose_label', 100)->default('pose');   // e.g. "Front Standing"
            $table->string('file_path', 500);                      // /storage/... relative path
            $table->text('prompt_used')->nullable();               // exact prompt sent to AI
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_persona_poses');
        Schema::table('model_personas', function (Blueprint $table) {
            $table->dropColumn(['character_seed', 'character_lock_prompt']);
        });
    }
};
