<?php
namespace App\Services\Banks;

use App\Services\BankTransactionInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UbaBank implements BankTransactionInterface
{
    private $baseUrl;
    private $username;
    private $password;
    private $accessToken;
    private $refreshToken;

    public function __construct()
    {
        $this->baseUrl = config('default.banks.uba.api_url');
        $this->username = config('default.banks.uba.username');
        $this->password = config('default.banks.uba.password');
    }

    public function authenticate(): bool
    {
        if (Cache::has('uba_access_token')) {
            $this->accessToken = Cache::get('uba_access_token');
            $this->refreshToken = Cache::get('uba_refresh_token');
            return true;
        }

        try {
            $response = Http::post($this->baseUrl . '/api/token/', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access'];
                $this->refreshToken = $data['refresh'];
                
                Cache::put('uba_access_token', $this->accessToken, now()->addMinutes(55));
                Cache::put('uba_refresh_token', $this->refreshToken, now()->addDays(7));
                
                return true;
            }

            throw new \Exception('Authentication failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('UBA Authentication Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function refreshToken(): bool
    {
        try {
            $response = Http::post($this->baseUrl . '/api/token/refresh/', [
                'refresh' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access'];
                Cache::put('uba_access_token', $this->accessToken, now()->addMinutes(55));
                return true;
            }

            throw new \Exception('Token refresh failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('UBA Token Refresh Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function fetchTransactions(string $accountNumber, string $date): array
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $formattedDate = Carbon::createFromFormat('Y-m-d', $date)->format('d-m-Y');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/api/fetch-records/', [
                'start_date' => $formattedDate,
                'end_date' => $formattedDate,
                'account_no' => $accountNumber,
            ]);

            if ($response->status() === 401) {
                $this->refreshToken();
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Accept' => 'application/json',
                ])->post($this->baseUrl . '/api/fetch-records/', [
                    'start_date' => $formattedDate,
                    'end_date' => $formattedDate,
                    'account_no' => $accountNumber,
                ]);
            }

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to fetch transactions: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('UBA Transaction Fetch Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function formatTransactions(array $transactions): array
    {
        $formatted = [];

        foreach ($transactions['records']['transactions'] ?? [] as $transaction) {
            $formatted[] = [
                'date' => $transaction['tran_date'],
                'amount' => number_format($transaction['amount'], 2),
                'reference' => $transaction['tran_id'],
                'type' => $transaction['part_tran_type'] === 'C' ? 'credit' : 'debit',
                'narration' => $transaction['narration'],
                'account_name' => $transaction['sender_account_name'] ?? 'N/A',
                'account_number' => $transaction['senders_account_number'] ?? 'N/A',
                'bank' => $this->getBankName(),
            ];
        }

        return $formatted;
    }

    public function getBankName(): string
    {
        return 'UBA';
    }
}