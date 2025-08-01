<?php

namespace App\Services\PaymentGateway;

use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

interface GatewayInterface
{
    /**
     * Process payment with this gateway
     *
     * @param Payment $payment
     * @param array $paymentData
     * @param float $totalAmount
     * @param Wallet|null $wallet
     * @param string $description
     * @return mixed
     */
    public function processPayment(
        Collection $invoice, 
        Payment $payment, 
        array $paymentData, 
        float $totalAmount, 
        ?Wallet $wallet, 
        ?string $description
    );

    /**
     * Handle gateway callback
     *
     * @param string $reference
     * @return Payment
     */
    public function handleCallback(string $reference): Payment;

    /**
     * Handle gateway webhook
     *
     * @param Request $request
     * @return mixed
     */
    public function handleWebhook(Request $request);

    /**
     * Verify a transaction
     *
     * @param string $reference
     * @return array
     */
    public function verifyTransaction(string $reference): array;


    
}