<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('theme')->nullable(); // summer|winter|luxury|streetwear|eid|...
            $table->string('status')->default('draft'); // draft|generating|ready|published
            $table->text('brief')->nullable();          // user's campaign brief
            $table->json('settings')->nullable();       // generation settings
            $table->timestamps();
        });

        Schema::create('campaign_garments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_garments');
        Schema::dropIfExists('campaigns');
    }
};
