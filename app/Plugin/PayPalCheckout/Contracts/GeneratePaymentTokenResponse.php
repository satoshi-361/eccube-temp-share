<?php

namespace Plugin\PayPalCheckout\Contracts;

use Plugin\PayPalCheckout\Entity\PaymentToken;

/**
 * Interface GeneratePaymentTokenResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface GeneratePaymentTokenResponse
{
    /**
     * @return PaymentToken[]
     */
    public function getPaymentTokens(): array;
}
