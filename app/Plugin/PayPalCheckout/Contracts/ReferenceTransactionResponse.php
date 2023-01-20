<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface ReferenceTransactionResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface ReferenceTransactionResponse
{
    /**
     * @return string
     */
    public function getBillingAgreementId(): string;
}
