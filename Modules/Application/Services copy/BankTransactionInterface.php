<?php
namespace App\Services;

interface BankTransactionInterface
{
    public function authenticate(): bool;
    public function fetchTransactions(string $accountNumber, string $date): array;
    public function formatTransactions(array $transactions): array;
    public function getBankName(): string;
}