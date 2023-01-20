<?php

namespace Plugin\CustomerReview4;

use Eccube\Common\EccubeNav;

class CustomerReview4Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'product' => [
                'children' => [
                    'review_list_admin' => [
                        'name' => 'customer_review4.admin.review_list.title',
                        'url' => 'admin_review_list',
                    ],
                ],
            ],
        ];
    }
}
