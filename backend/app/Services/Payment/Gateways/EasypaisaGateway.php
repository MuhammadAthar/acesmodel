<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\Concerns\ActivatesPlan;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EasypaisaGateway implements PaymentGatewayInterface
{
    use ActivatesPlan;

    private string $storeId;
    private string $hashKey;
    private string $apiUrl = 'https://easypay.easypaisa.com.pk/easypay/Index.jsf';

    public function __construct()
    {
        $this->storeId = config('services.easypaisa.store_id');
        $this->hashKey = config('services.easypaisa.hash_key');
    }

    public function initiate(User $user, string $plan, Request $request): array
    {
        $orderId     = 'THREAD_' . $user->id . '_' . time();
        $amount      = $this->planPrice($plan);
        $amountFormatted = number_format($amount / 100, 2, '.', '');

        $hashString = $this->storeId . $orderId . $amountFormatted . 'PKR' . $this->hashKey;
        $hash       = strtoupper(hash('sha256', $hashString));

        $payload = [
            'storeId'       => $this->storeId,
            'orderId'       => $orderId,
            'transactionAmount' => $amountFormatted,
            'mobileAccountNo'   => '',
            'emailAddress'  => $user->email,
            'transactionType' => 'MA',
            'tokenExpiry'   => now()->addHour()->format('Ymd His'),
            'bankIdentificationNumber' => '',
            'encryptedHashRequest' => $hash,
            'merchantPaymentMethod' => 'MA',
            'returnUrl'     => config('app.frontend_url') . '/billing?gateway=easypaisa&order=' . $orderId,
            'postBackURL'   => route('api.payments.callback', 'easypaisa'),
        ];

        // Store pending payment
        Payment::create([
            'user_id'          => $user->id,
            'gateway'          => 'easypaisa',
            'gateway_payment_id'=> $orderId,
            'plan'             => $plan,
            'amount'           => $amount,
            'currency'         => 'PKR',
            'status'           => 'pending',
            'gateway_response' => $payload,
        ]);

        return ['type' => 'redirect', 'checkout_url' => $this->apiUrl . '?' . http_build_query($payload)];
    }

    public function handleCallback(Request $request): array
    {
        $orderId = $request->input('orderId');
        $status  = $request->input('paymentStatus'); // PAID | UNPAID

        $payment = Payment::where('gateway_payment_id', $orderId)
            ->where('gateway', 'easypaisa')
            ->firstOrFail();

        if ($status === 'PAID') {
            $payment->update([
                'status'           => 'completed',
                'paid_at'          => now(),
                'gateway_response' => $request->all(),
            ]);

            $user = User::findOrFail($payment->user_id);
            $this->activatePlan($user, $payment->plan, 'easypaisa', null, $payment);
        } else {
            $payment->update(['status' => 'failed', 'gateway_response' => $request->all()]);
        }

        return ['received' => true];
    }

    private function planPrice(string $plan): int
    {
        return match ($plan) {
            'starter'    => 8000_00,  // ~PKR 8000 (~$29)
            'growth'     => 22000_00, // ~PKR 22000 (~$79)
            'agency'     => 55000_00, // ~PKR 55000 (~$199)
            default      => 0,
        };
    }
}
