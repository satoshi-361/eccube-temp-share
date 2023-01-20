<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface CaptureTransactionResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface CaptureTransactionResponse
{
    /**
     * @return string
     */
    public function getCaptureTransactionId(): string;

    /**
     * @return bool
     */
    public function isOk(): bool;

    /**
     * @return bool
     */
    public function isNg(): bool;

    /**
     * @return string
     */
    public function getDebugId(): string;
}
