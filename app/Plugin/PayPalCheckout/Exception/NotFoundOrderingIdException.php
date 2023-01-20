<?php

namespace Plugin\PayPalCheckout\Exception;

use Throwable;

/**
 * Class NotFoundOrderingIdException
 * @package Plugin\PayPalCheckout\Exception
 */
class NotFoundOrderingIdException extends PayPalCheckoutException
{
    /**
     * NotFoundOrderingIdException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $message = "セッションが無効です。再度購入手続きをやり直してください。";
        parent::__construct($message, $code, $previous);
    }
}
