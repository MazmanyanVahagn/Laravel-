<?php
/**
 * Created by PhpStorm.
 * User: NUM-11
 * Date: 11/09/2018
 * Time: 20:22
 */

namespace Bank\Models\MySQL;


use Illuminate\Database\Eloquent\Model;

class BankCoinPayment extends Model
{
    const IPN_APPROVED = 1;
    const IPN_PENDING = 0;

    /**
     * Status texts from coinpayments API
     * @var array
     */
    private $statusTexts = [
        -2  => 'Paypal Refund or Reversal',
        -2  => 'Cancelled / Timed Out',
        0   => 'Waiting for buyer funds',
        1   => 'We have confirmed coin reception from the buyer',
        2   => 'Queued for nightly payout (if you have the Payout Mode for this coin set to Nightly)',
        3   => 'Paypal Pending (eChecks or other types of holds)',
        100 => 'Payment Complete. We have sent your coins to your payment address or 3rd party payment system reports the payment complete'
    ];

    /**
     * Check if payment completed
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status >= 100 || $this->status == 2;
    }

    /**
     * Check if payment failed
     * @return bool
     */
    public function isFailed()
    {
        return $this->status < 0;
    }

    /**
     * Check if payment is under pending
     * @return bool
     */
    public function isPending()
    {
        return $this->status >= 0 && $this->status < 100;
    }

    /**
     * Get concrete status text
     * @return mixed|null
     */
    public function getConcreteStatus()
    {
        return $this->statusTexts[$this->status] ?? null;
    }

    /**
     * Get current status text
     * @return mixed
     */
    public function getStatusText()
    {
        return $this->status_text;
    }
}