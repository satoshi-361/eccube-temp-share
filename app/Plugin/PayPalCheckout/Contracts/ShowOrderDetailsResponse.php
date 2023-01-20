<?php

namespace Plugin\PayPalCheckout\Contracts;

use Plugin\PayPalCheckout\Entity\PaymentToken;

/**
 * Interface ShowOrderDetailsResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface ShowOrderDetailsResponse
{
    /**
     * @return string
     */
    public function getLiabilityShift(): string;

    /**
     * @return string
     */
    public function getEnrollmentStatus(): string;

    /**
     * @return string
     */
    public function getAuthenticationStatus(): string;

    /**
     * @return bool
     */
    public function isOk(): bool;
}
