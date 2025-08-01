<?php

namespace App\Services;

use Unicodeveloper\Paystack\Paystack;

class PaystackEx extends Paystack
{
    /**
     * Get the current payment session timeout value.
     *
     * @return array
     */
    public function getPaymentSessionTimeout(): array
    {
        $url = '/integration/payment_session_timeout';

        $response = $this->client->get($url);
        return json_decode($response->getBody(), true);
    }

    /**
     * Update the payment session timeout value.
     *
     * @param int $timeout
     * @return array
     */
    public function updatePaymentSessionTimeout(int $timeout): array
    {
        $url = '/integration/payment_session_timeout';

        $response = $this->client->put($url, [
            'json' => ['timeout' => $timeout]
        ]);

        return json_decode($response->getBody(), true);
    }
}
