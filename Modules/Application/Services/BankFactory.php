<?php
namespace App\Services;

use App\Services\Banks\UbaBank;
use App\Services\Banks\ZenithBank;
use InvalidArgumentException;

class BankFactory
{
    public static function create(string $bankCode): BankTransactionInterface
    {
        switch (strtolower($bankCode)) {
            case 'uba':
                return new UbaBank();
            case 'access':

            default:
                throw new InvalidArgumentException("Unsupported bank: {$bankCode}");
        }
    }
}