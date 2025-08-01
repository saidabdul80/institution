<?php

// app/Services/RemitaService.php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class RemitaService
{
    protected $apiBaseUrl;
    protected $apiKey;
    protected $merchantId;

    public function __construct()
    {
        $this->apiBaseUrl = config('remita.api_base_url');
        $this->apiKey = config('remita.api_key');
        $this->merchantId = config('remita.merchant_id');
    }

    public function generatePaymentUrl($amount, $payerName, $payerEmail)
    {
        $url = $this->apiBaseUrl . '/path/to/endpoint';

        $response = Http::withHeaders(
            [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($url, [
                'merchantId' => $this->merchantId,
                'amount' => $amount,
                'payerName' => $payerName,
                'payerEmail' => $payerEmail,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
?>