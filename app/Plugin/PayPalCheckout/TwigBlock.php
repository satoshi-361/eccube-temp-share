<?php

namespace Plugin\PayPalCheckout;

use Eccube\Common\EccubeTwigBlock;

/**
 * Class TwigBlock
 * @package Plugin\PayPalCheckout
 */
class TwigBlock implements EccubeTwigBlock
{
    /**
     * @return array
     */
    public static function getTwigBlock()
    {
        return [
            '@PayPalCheckout/default/Block/paypal_logo.twig'
        ];
    }
}
