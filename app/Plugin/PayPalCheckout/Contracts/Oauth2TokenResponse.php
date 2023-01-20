<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface Oauth2TokenResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface Oauth2TokenResponse
{
    /**
     * @return string
     */
    public function getAccessToken(): string;
}
