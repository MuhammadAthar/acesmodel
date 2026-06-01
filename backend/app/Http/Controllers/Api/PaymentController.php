<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentManager $payments)
    {
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            ['key' => 'free',       'label' => 'Free',       'price' => 0,   'credits' => 10,   'currency' => 'USD'],
            ['key' => 'starter',    'label' => 'Starter',    'price' => 29,  'credits' => 200,  'currency' => 'USD'],
            ['key' => 'growth',     'label' => 'Growth',     'price' => 79,  'credits' => 600,  'currency' => 'USD'],
            ['key' => 'agency',     'label' => 'Agency',     'price' => 199, 'credits' => 2000, 'currency' => 'USD'],
            ['key' => 'enterprise', 'label' => 'Enterprise', 'price' => null,'credits' => null,  'currency' => 'USD'],
        ]);
    }

    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan'    => ['required', 'in:starter,growth,agency,enterprise'],
            'gateway' => ['required', 'in:stripe,easypaisa,jazzcash,bank_transfer'],
        ]);

        $result = $this->payments->gateway($data['gateway'])
            ->initiate($request->user(), $data['plan'], $request);

        return response()->json($result);
    }

    public function callback(Request $request, string $gateway): JsonResponse
    {
        $result = $this->payments->gateway($gateway)->handleCallback($request);
        return response()->json($result);
    }

    // Bank transfer: user uploads receipt, admin confirms
    public function bankTransferSubmit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan'    => ['required', 'in:starter,growth,agency,enterprise'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $receiptPath = $request->file('receipt')->store('receipts', 'public');

        $payment = Payment::create([
            'user_id'     => $request->user()->id,
            'gateway'     => 'bank_transfer',
            'plan'        => $data['plan'],
            'amount'      => $this->planPrice($data['plan']),
            'currency'    => 'USD',
            'status'      => 'pending',
            'receipt_path'=> $receiptPath,
        ]);

        return response()->json(['message' => 'Receipt submitted. Admin will verify within 24 hours.', 'payment' => $payment], 201);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json($request->user()->payments()->latest()->paginate(20));
    }

    public function activeSubscription(Request $request): JsonResponse
    {
        $sub = $request->user()->subscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();
        return response()->json($sub);
    }

    private function planPrice(string $plan): int
    {
        return match ($plan) {
            'starter'    => 2900,
            'growth'     => 7900,
            'agency'     => 19900,
            'enterprise' => 0,
            default      => 0,
        };
    }
}
