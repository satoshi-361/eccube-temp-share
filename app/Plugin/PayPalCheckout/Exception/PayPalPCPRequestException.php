<?php

namespace Plugin\PayPalCheckout\Exception;

use stdClass;
use Throwable;

/**
 * Class PayPalPCPRequestException
 * @package Plugin\PayPalCheckout\Exception
 */
class PayPalPCPRequestException extends PayPalCheckoutException
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
        if(isset($array_message->debug_id)) {
            $debug_id = $array_message->debug_id;
        } else {
            $debug_id = '';
        }

        $message = "PayPal連携でエラーが発生しました。エラーが繰り返し発生する場合は、エラーの詳細についてPayPalカスタマーサポートにお問い合わせください（{$debug_id}）";
        parent::__construct($message, $code, $previous);
    }
}
