<?php
namespace App\Services;

use Aws\SesV2\SesV2Client;
use Aws\Exception\AwsException;

class SesSuppressionService
{
    protected $client;

    public function __construct()
    {
        $this->client = new SesV2Client([
            'region' => config('services.ses.region', 'eu-west-1'),
            'version' => 'latest',
            'credentials' => [
                'key'    => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);
    }

    public function isSuppressed(string $email): bool|string
    {
        try {
            $result = $this->client->getSuppressedDestination([
                'EmailAddress' => $email,
            ]);

            return $result['SuppressedDestination']['Reason'] ?? true;

        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'NotFoundException') {
                return false; // Not suppressed
            }
            throw $e;
        }
    }
}
