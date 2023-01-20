<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface GenerateClientTokenResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface GenerateClientTokenResponse
{
    /**
     * @return string
     */
    public function getClientToken(): string;
}
