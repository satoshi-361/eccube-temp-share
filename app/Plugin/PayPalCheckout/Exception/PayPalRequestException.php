<?php

namespace Plugin\PayPalCheckout\Exception;

use stdClass;
use Throwable;

/**
 * Class PayPalRequestException
 * @package Plugin\PayPalCheckout\Exception
 */
class PayPalRequestException extends PayPalCheckoutException
{
    /**
     * PayPalRequestException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        /** @var stdClass $array_message */
        $array_message = json_decode($message);
        $debug_id = $array_message->debug_id ?? 'no debug_id';
        $message = "PayPal決済でエラーが発生しました。エラーが繰り返し発生する場合は、エラーの詳細についてPayPalカスタマーサポートにお問い合わせください（{$debug_id}）";
        parent::__construct($message, $code, $previous);
    }
}
