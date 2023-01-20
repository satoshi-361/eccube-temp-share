<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface MerchantCredentialResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface MerchantCredentialResponse
{
    /**
     * @return string
     */
    public function getClientId(): string;
    public function getClientSecret(): string;
}
