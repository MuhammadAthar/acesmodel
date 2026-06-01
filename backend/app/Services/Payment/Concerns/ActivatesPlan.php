<?php

namespace App\Services\Payment\Concerns;

use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;

trait ActivatesPlan
{
    private array $planCredits = [
        'free'       => 10,
        'starter'    => 200,
        'growth'     => 600,
        'agency'     => 2000,
        'enterprise' => 9999,
    ];

    protected function activatePlan(
        User $user,
        string $plan,
        string $gateway,
        ?string $gatewaySubscriptionId = null,
        ?Payment $payment = null
    ): void {
        // Deactivate any existing subscriptions
        $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        Subscription::create([
            'user_id'                  => $user->id,
            'plan'                     => $plan,
            'gateway'                  => $gateway,
            'gateway_subscription_id'  => $gatewaySubscriptionId,
            'status'                   => 'active',
            'current_period_start'     => now(),
            'current_period_end'       => now()->addMonth(),
        ]);

        $credits = $this->planCredits[$plan] ?? 0;
        $user->update(['plan' => $plan, 'credits' => $credits]);

        CreditTransaction::create([
            'user_id'        => $user->id,
            'amount'         => $credits,
            'type'           => 'plan_grant',
            'description'    => "Credits granted for {$plan} plan",
            'reference_type' => $payment ? Payment::class : User::class,
            'reference_id'   => $payment?->id ?? $user->id,
        ]);
    }
}
