<?php


namespace App\Traits;

use App\Models\Wallet;


trait HasWallet
{
    // Returns the wallet
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner', 'owner_type', 'wallet_number', 'wallet_number');
        // return Wallet::where(['wallet_number' => $this->wallet_number])->first();
    }

    /**
     * Determine if the user can withdraw the given amount
     * @param  integer $amount (in kobo)
     * @return boolean
     */
    public function isSufficient($amount)
    {
        $wallet = $this->getWallet();

        return $wallet->balance >= $amount;
    }

    /**
     * Determine if users balance + bonus is sufficient
     * @param  integer $amount
     * @return boolean
     */
    public function isAbsolutelySufficient($amount)
    {
        $wallet = $this->getWallet();

        // Get the total balance (Balance + Bonus)
        $balance = $wallet->balance;
        $bonus = $wallet->bonus;
        return ($balance + $bonus) >= $amount;
    }

    /**
     * Crediting the users wallet
     * @param integer $amount (in kobo)
     * @param bool $bonus
     */
    public function credit($amount, $bonus = false)
    {
        $wallet = $this->getWallet();
        if ($bonus) {
            $balance = $wallet->bonus + $amount;
            $response = $wallet->update(['bonus' => $balance]);
        } else {
            $balance = $wallet->balance + $amount;
            $response = $wallet->update(['balance' => $balance]);
        }
        return $response;
    }

    /**
     * Keep record of total collection from applicants/students
     * @param integer $amount (in kobo)
     * @param bool $bonus
     */
    public function addToTotalCollection($amount)
    {
        $wallet = $this->getWallet();
        $balance = $wallet->total_collection + $amount;
        $response = $wallet->update(['total_collection' => $balance]);
        return $response;
    }

    /**
     * Move credits to this account
     * @param integer $amount (in kobo)
     * @param bool $bonus
     */
    public function debit($amount)
    {
        if ($this->isAbsolutelySufficient($amount)) {
            $wallet = $this->getWallet();

            $balance = $wallet->balance;
            $bonusBal = $wallet->bonus;

            // To know if the bonus will be spent
            // Negative value indicates that Main Balance can bear the charges
            // Positive value indicates that Main Balance can't bear all the charges, hence, Bonus will also be used.
            $deficit = $amount - $balance;

            // Check if normal balance can cover the amount
            // If not debit the bonus wallet too.
            if ($deficit > 0) {
                // For Bonus to be considered, then balance is not enough and should now be 0
                $wallet->update(['balance' => 0]);

                // Deduct the deficit from the bonus
                $bonusBalance = $bonusBal - $deficit;

                $wallet->update(['bonus' => $bonusBalance]);
            } else {
                $balance = $wallet->balance - $amount;

                $wallet->update(['balance' => $balance]);
            }
            return true;
        }
        return false;
    }

    public function balance()
    {
        $wallet = $this->getWallet();

        return $wallet->balance ?? 0;
    }

    public function getTotalBalance(string $currency = null)
    {
        $wallet = $this->getWallet();

        $balance = $wallet->balance;
        $bonus = $wallet->bonus;
        return ($balance + $bonus);
    }

    protected function getWallet(): Wallet |  null
    {
        return $this->wallet;
    }
}
