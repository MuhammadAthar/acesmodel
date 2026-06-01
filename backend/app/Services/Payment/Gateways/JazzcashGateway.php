<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\Concerns\ActivatesPlan;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

class JazzcashGateway implements PaymentGatewayInterface
{
    use ActivatesPlan;

    private string $merchantId;
    private string $password;
    private string $integritySalt;
    private string $postUrl = 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';

    public function __construct()
    {
        $this->merchantId    = config('services.jazzcash.merchant_id');
        $this->password      = config('services.jazzcash.password');
        $this->integritySalt = config('services.jazzcash.integrity_salt');
    }

    public function initiate(User $user, string $plan, Request $request): array
    {
        $txnRefNo = 'T' . date('YmdHis') . rand(1000, 9999);
        $amount   = $this->planPrice($plan);
        $dateTime = date('YmdHis');
        $expiryDateTime = date('YmdHis', strtotime('+1 hour'));

        $data = [
            'pp_Version'        => '1.1',
            'pp_TxnType'        => 'MWALLET',
            'pp_Language'       => 'EN',
            'pp_MerchantID'     => $this->merchantId,
            'pp_Password'       => $this->password,
            'pp_TxnRefNo'       => $txnRefNo,
            'pp_Amount'         => str_pad($amount, 12, '0', STR_PAD_LEFT),
            'pp_TxnCurrency'    => 'PKR',
            'pp_TxnDateTime'    => $dateTime,
            'pp_BillReference'  => 'billRef',
            'pp_Description'    => "Aces Model {$plan} plan",
            'pp_TxnExpiryDateTime' => $expiryDateTime,
            'pp_ReturnURL'      => config('app.frontend_url') . '/billing?gateway=jazzcash&txn=' . $txnRefNo,
            'pp_SecureHash'     => '',
        ];

        // Build secure hash
        ksort($data);
        $hashStr = $this->integritySalt;
        foreach ($data as $key => $val) {
            if ($key !== 'pp_SecureHash' && $val !== '') {
                $hashStr .= '&' . $val;
            }
        }
        $data['pp_SecureHash'] = strtoupper(hash_hmac('sha256', $hashStr, $this->integritySalt));

        Payment::create([
            'user_id'           => $user->id,
            'gateway'           => 'jazzcash',
            'gateway_payment_id'=> $txnRefNo,
            'plan'              => $plan,
            'amount'            => $amount,
            'currency'          => 'PKR',
            'status'            => 'pending',
            'gateway_response'  => $data,
        ]);

        return ['type' => 'form_post', 'post_url' => $this->postUrl, 'form_data' => $data];
    }

    public function handleCallback(Request $request): array
    {
        $txnRef  = $request->input('pp_TxnRefNo');
        $respCode = $request->input('pp_ResponseCode'); // 000 = success

        $payment = Payment::where('gateway_payment_id', $txnRef)
            ->where('gateway', 'jazzcash')
            ->firstOrFail();

        if ($respCode === '000') {
            $payment->update([
                'status'           => 'completed',
                'paid_at'          => now(),
                'gateway_response' => $request->all(),
            ]);
            $user = User::findOrFail($payment->user_id);
            $this->activatePlan($user, $payment->plan, 'jazzcash', null, $payment);
        } else {
            $payment->update(['status' => 'failed', 'gateway_response' => $request->all()]);
        }

        return ['received' => true];
    }

    private function planPrice(string $plan): int
    {
        return match ($plan) {
            'starter'    => 800000,
            'growth'     => 2200000,
            'agency'     => 5500000,
            default      => 0,
        };
    }
}
