<?php

namespace Plugin\PayPalCheckout\Service;

/**
 * Class LoggerService
 */
class LoggerService
{
    /**
     * ログヘッダー
     */
    const LOG_HEADER = '[PayPalCheckout] ';

    /**
     * LoggerService constructor.
     */
    public function __construct()
    {
    }

    public function emergency($message, array $context = [])
    {
        logs('PayPalCheckout')->emergency(self::LOG_HEADER . $message, $context);
    }

    public function alert($message, array $context = [])
    {
        logs('PayPalCheckout')->alert(self::LOG_HEADER . $message, $context);
    }

    public function critical($message, array $context = [])
    {
        logs('PayPalCheckout')->critical(self::LOG_HEADER . $message, $context);
    }

    public function error($message, array $context = [])
    {
        logs('PayPalCheckout')->error(self::LOG_HEADER . $message, $context);
    }

    public function warning($message, array $context = [])
    {
        logs('PayPalCheckout')->warning(self::LOG_HEADER . $message, $context);
    }

    public function notice($message, array $context = [])
    {
        logs('PayPalCheckout')->notice(self::LOG_HEADER . $message, $context);
    }

    public function info($message, array $context = [])
    {
        logs('PayPalCheckout')->info(self::LOG_HEADER . $message, $context);
    }

    public function debug($message, array $context = [])
    {
        logs('PayPalCheckout')->debug(self::LOG_HEADER . $message, $context);
    }
}
