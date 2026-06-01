<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\Concerns\ActivatesPlan;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

class BankTransferGateway implements PaymentGatewayInterface
{
    use ActivatesPlan;

    public function initiate(User $user, string $plan, Request $request): array
    {
        return [
            'type'    => 'bank_transfer_instructions',
            'details' => [
                'bank'          => config('services.bank_transfer.bank_name'),
                'account_title' => config('services.bank_transfer.account_title'),
                'account_number'=> config('services.bank_transfer.account_number'),
                'iban'          => config('services.bank_transfer.iban'),
                'instructions'  => 'Transfer the plan amount and upload your receipt. Admin will verify within 24 hours.',
            ],
        ];
    }

    public function handleCallback(Request $request): array
    {
        // Bank transfer is handled manually via admin panel
        return ['received' => true];
    }

    public function adminConfirm(Payment $payment): void
    {
        $payment->update(['status' => 'completed', 'paid_at' => now()]);
        $user = User::findOrFail($payment->user_id);
        $this->activatePlan($user, $payment->plan, 'bank_transfer', null, $payment);
    }
}
