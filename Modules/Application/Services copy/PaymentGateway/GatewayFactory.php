<?php

namespace App\Services\PaymentGateway;

use App\Services\PaymentGateway\Gateways\PaystackGateway;
use App\Services\PaymentGateway\Gateways\RemitaGateway;
use App\Services\PaymentGateway\Gateways\WalletGateway;
use App\Services\PaymentGateway\Gateways\EtranzactGateway;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class GatewayFactory
{
    protected $gateways = [
        'paystack' => PaystackGateway::class,
        'remita' => RemitaGateway::class,
        'wallet' => WalletGateway::class,
        'etranzact' => EtranzactGateway::class,
    ];

    /**
     * Create a gateway instance
     *
     * @param string $gateway
     * @return GatewayInterface
     * @throws InvalidArgumentException
     */
    public function create(string $gateway): GatewayInterface
    {
        try{
            if (!isset($this->gateways[strtolower($gateway)])) {
                throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}");
            }

            $gatewayClass = $this->gateways[$gateway];

            if (!class_exists($gatewayClass)) {
                throw new InvalidArgumentException("Gateway class {$gatewayClass} does not exist");
            }

            $instance = app($gatewayClass);

            if (!$instance instanceof GatewayInterface) {
                throw new InvalidArgumentException("Gateway {$gatewayClass} must implement GatewayInterface");
            }

            return $instance;
        }catch(\Throwable  $e){
            Log::error("Gateway processing error: " . $e->getMessage());
            throw new \Exception($e, 400);
        }
    }

    /**
     * Register a new gateway
     *
     * @param string $name
     * @param string $gatewayClass
     * @return void
     */
    public function register(string $name, string $gatewayClass): void
    {
        $this->gateways[$name] = $gatewayClass;
    }

    /**
     * Get all registered gateways
     *
     * @return array
     */
    public function getRegisteredGateways(): array
    {
        return array_keys($this->gateways);
    }
}