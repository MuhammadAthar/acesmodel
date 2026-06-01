<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('original_image');      // path to uploaded image
            $table->string('processed_image')->nullable();
            // AI-extracted garment profile
            $table->string('category')->nullable();   // shirt|pants|dress|jacket|...
            $table->string('gender')->nullable();     // male|female|unisex|kids
            $table->string('season')->nullable();     // spring|summer|autumn|winter|all
            $table->json('colors')->nullable();       // ["black","white"]
            $table->string('fabric')->nullable();     // cotton|polyester|silk|...
            $table->string('texture')->nullable();    // smooth|knit|denim|...
            $table->string('style')->nullable();      // casual|formal|streetwear|luxury|...
            $table->json('ai_tags')->nullable();      // additional AI tags
            $table->boolean('analyzed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garments');
    }
};
