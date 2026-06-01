<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\Gateways\EasypaisaGateway;
use App\Services\Payment\Gateways\JazzcashGateway;
use App\Services\Payment\Gateways\BankTransferGateway;

class PaymentManager
{
    public function gateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'stripe'        => new StripeGateway(),
            'easypaisa'     => new EasypaisaGateway(),
            'jazzcash'      => new JazzcashGateway(),
            'bank_transfer' => new BankTransferGateway(),
            default         => throw new \InvalidArgumentException("Unknown payment gateway: {$name}"),
        };
    }
}
