<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            // Brand DNA — AI-extracted
            $table->json('colors')->nullable();           // ["#FF5733", "#C70039"]
            $table->string('tone')->nullable();           // luxury|casual|streetwear|minimal
            $table->string('photography_style')->nullable(); // editorial|lifestyle|studio
            $table->json('model_preferences')->nullable(); // {"ethnicity":"south_asian","age":"25-35","size":"regular"}
            $table->json('aesthetic_tags')->nullable();   // ["minimalist","dark","editorial"]
            $table->boolean('dna_analyzed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
