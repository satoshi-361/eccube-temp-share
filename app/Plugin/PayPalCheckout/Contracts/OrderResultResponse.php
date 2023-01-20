<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface OrderResultResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface OrderResultResponse
{
    /**
     * @return string
     */
    public function getOrderingId(): string;
}
