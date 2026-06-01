<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan');             // free|starter|growth|agency|enterprise
            $table->string('gateway');          // stripe|easypaisa|jazzcash|bank_transfer
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->string('status');           // active|cancelled|expired|pending
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');          // stripe|easypaisa|jazzcash|bank_transfer
            $table->string('gateway_payment_id')->nullable();
            $table->string('plan');
            $table->unsignedInteger('amount');  // in cents/paisa
            $table->string('currency')->default('USD');
            $table->string('status');           // pending|completed|failed|refunded
            $table->json('gateway_response')->nullable();
            $table->string('receipt_path')->nullable(); // for bank transfer receipts
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');          // positive = add, negative = deduct
            $table->string('type');             // plan_grant|generation|refund|admin_grant
            $table->string('description')->nullable();
            $table->morphs('reference');        // polymorphic: Payment, GeneratedAsset, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
