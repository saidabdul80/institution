<?php
namespace App\Services;

use App\Enums\RemitaEndPoint;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;

/**
 * Class Remita
 *
 * This file is part of Laravel Remita Package
 *
 * @author (c) Generah Ben <generahben@gmail.com>
 * @since 9/18/2022
 *
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */
class Remita
{

    private Client $client;
    private string $hashFormula;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('remita.base_uri'),
            'headers' => [
                'Accept'     => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function generateRRR(array $body = []): array
    {
        $this->hashFormula = config('remita.merchant_id') . $body['serviceTypeId'] . $body['orderId'] . $body['amount'] . Config::get('remita.api_key');
        $res = $this->client->post(RemitaEndPoint::GENERATE_INVOICE_STANDARD->value, [
            'json' => $body,
            'headers' => [
                'Authorization' => $this->getRequestAuthorization()
            ]
        ]);

        return $this->response($res);
    }

    /**
     * @throws GuzzleException
     */
    public function getTransactionStatusByRRR(string $rrr): array
    {
        $this->setTransactionStatusHashFormula($rrr);
        $url = sprintf(RemitaEndPoint::TRANSACTION_STATUS_BY_RRR->value, Config::get('remita.consumer_key'), $rrr, $this->getApiHash($this->hashFormula));

        $res = $this->client->get($url, [
            'headers' => [
                'Authorization' => $this->getRequestAuthorization()
            ]
        ]);

        return $this->response($res);
    }

    /**
     * @throws GuzzleException
     */
    public function getTransactionStatusByOrderId(string $orderId): array
    {
        $this->setTransactionStatusHashFormula($orderId);

        $url = sprintf(RemitaEndPoint::TRANSACTION_STATUS_BY_ORDER_ID->value, Config::get('remita.consumer_key'), $orderId, $this->getApiHash($this->hashFormula));

        $res = $this->client->get($url, [
            'headers' => [
                'Authorization' => $this->getRequestAuthorization()
            ]
        ]);

        return $this->response($res);
    }


    private function getApiHash(string $hashFormula): string
    {
        return hash('sha512', $hashFormula);
    }

    private function getRequestAuthorization(): string
    {
        return 'remitaConsumerKey=' . config('remita.consumer_key') . ',remitaConsumerToken=' . $this->getApiHash($this->hashFormula);
    }


    private function response(\Psr\Http\Message\ResponseInterface $res): array
    {
        return json_decode($res->getBody()->getContents(), true);
    }

    private function setTransactionStatusHashFormula(string $orderIdOrRRR): void
    {
        $this->hashFormula = $orderIdOrRRR . Config::get('remita.api_key') . Config::get('remita.merchant_id');
    }

}