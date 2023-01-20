<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface PartnerReferralsResponse
 * @package Plugin\PayPalCheckout\Contracts
 */
interface PartnerReferralsResponse
{
    /**
     * @return string
     */
    public function getActionUrl(): string;
}
