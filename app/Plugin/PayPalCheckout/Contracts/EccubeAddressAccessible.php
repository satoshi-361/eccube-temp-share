<?php

namespace Plugin\PayPalCheckout\Contracts;

/**
 * Interface EccubeAddressAccessible
 * @package Plugin\PayPalCheckout\Contracts
 */
interface EccubeAddressAccessible
{
    /**
     * @return array
     */
    public function getEccubeAddressFormat(): array;
}
