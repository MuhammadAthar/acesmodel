<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_personas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // Display name, e.g. "Sophia"
            $table->string('gender');                           // male|female|boy|girl|child|non_binary
            $table->unsignedTinyInteger('age')->nullable();     // e.g. 25
            $table->string('nationality')->nullable();          // e.g. "Pakistani"
            $table->string('ethnicity')->nullable();            // e.g. "South Asian"
            $table->string('skin_tone')->nullable();            // e.g. "medium brown"
            $table->string('body_type')->nullable();            // slim|athletic|curvy|plus_size|petite
            $table->string('hair')->nullable();                 // e.g. "long black hair"
            $table->string('best_for')->nullable();             // e.g. "casual wear, youth brands, summer"
            $table->text('description')->nullable();            // Free-form AI prompt override
            $table->string('avatar_url')->nullable();           // Preview thumbnail for admin/studio UI
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_personas');
    }
};
