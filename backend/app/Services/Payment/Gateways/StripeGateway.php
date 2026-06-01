<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\Concerns\ActivatesPlan;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    use ActivatesPlan;

    private array $planPriceIds;

    public function __construct()
    {
        $this->planPriceIds = [
            'starter'    => config('services.stripe.price_starter'),
            'growth'     => config('services.stripe.price_growth'),
            'agency'     => config('services.stripe.price_agency'),
        ];
    }

    public function initiate(User $user, string $plan, Request $request): array
    {
        $priceId = $this->planPriceIds[$plan] ?? null;

        if (!$priceId) {
            return ['type' => 'contact_sales', 'message' => 'Contact sales for Enterprise.'];
        }

        $response = Http::withToken(config('services.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode'                 => 'subscription',
                'customer_email'       => $user->email,
                'line_items[0][price]' => $priceId,
                'line_items[0][quantity]' => 1,
                'success_url'          => config('app.frontend_url') . '/billing?success=1&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => config('app.frontend_url') . '/billing?cancelled=1',
                'metadata[user_id]'    => $user->id,
                'metadata[plan]'       => $plan,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Stripe error: ' . $response->body());
        }

        return [
            'type'         => 'redirect',
            'checkout_url' => $response->json('url'),
        ];
    }

    public function handleCallback(Request $request): array
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::warning('Stripe webhook signature invalid', ['error' => $e->getMessage()]);
            abort(400, 'Invalid signature.');
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            $userId  = $session['metadata']['user_id'];
            $plan    = $session['metadata']['plan'];
            $user    = User::findOrFail($userId);

            $payment = Payment::create([
                'user_id'          => $user->id,
                'gateway'          => 'stripe',
                'gateway_payment_id'=> $session['id'],
                'plan'             => $plan,
                'amount'           => $session['amount_total'],
                'currency'         => strtoupper($session['currency']),
                'status'           => 'completed',
                'gateway_response' => $session,
                'paid_at'          => now(),
            ]);

            $this->activatePlan($user, $plan, 'stripe', $session['subscription'] ?? null, $payment);
        }

        return ['received' => true];
    }
}
