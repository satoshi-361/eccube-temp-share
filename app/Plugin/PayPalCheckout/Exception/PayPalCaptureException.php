<?php

namespace Plugin\PayPalCheckout\Exception;

use stdClass;
use Throwable;

/**
 * Class PayPalCaptureException.
 */
class PayPalCaptureException extends PayPalCheckoutException
{
    /**
     * PayPalCaptureException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($debug_id = "", $code = 0, Throwable $previous = null)
    {
        $debug_id = $debug_id ?? 'no debug_id';
        $message = "PayPal決済でエラーが発生しました。エラーが繰り返し発生する場合は、エラーの詳細についてPayPalカスタマーサポートにお問い合わせください（{$debug_id}）";
        parent::__construct($message, $code, $previous);
    }
}
