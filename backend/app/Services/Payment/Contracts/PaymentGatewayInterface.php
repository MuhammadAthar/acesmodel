<?php

namespace App\Services\Payment\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment/subscription.
     * Returns redirect URL or checkout data for the frontend.
     */
    public function initiate(User $user, string $plan, Request $request): array;

    /**
     * Handle the callback/webhook from the gateway.
     */
    public function handleCallback(Request $request): array;
}
