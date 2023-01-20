<?php

namespace Plugin\PayPalCheckout;

use Eccube\Common\EccubeNav;

/**
 * Class Nav
 * @package Plugin\PayPalCheckout
 */
class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
// 決済状況一覧ページは一旦メニューから消しておく
//                    'paypal_transaction' => [
//                        'name' => 'paypal.admin.nav.transactions',
//                        'url' => 'paypal_admin_payment_status'
//                    ],
// プラグインの初期リリース(v1.0.0)には、定期決済は含めない
//                    'paypal_subscribing_customer' => [
//                        'name' => 'paypal.admin.nav.subscribing_customers',
//                        'url' => 'paypal_admin_subscribing_customer_pageno'
//                    ]
                ]
            ]
        ];
    }
}
