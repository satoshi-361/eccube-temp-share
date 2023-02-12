<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Entity\Order;

class GetProductInformationFromOrderService
{
    /**
     * 受注から代表商品情報を取得
     *
     * @return array
     */
    public function handle(Order $Order)
    {
        foreach ($Order->getOrderItems() as $orderItem) {
            if (!$orderItem->isProduct()) {
                continue;
            }
            $item_code = $orderItem->getProductCode();

            // 空の場合は仮の値をセット
            if (empty($item_code)) {
                $item_code = 'no_code';
            } else {
                /**
                 * 商品コードを整形
                 * 1. 全角→半角
                 * 2. 許容されない文字を削除
                 * 3. 64byteに丸め
                 */
                $item_code = mb_convert_kana($item_code, 'kvrn');
                $item_code = preg_replace('/[^a-zA-Z0-9\.\-\+\/]/', '', $item_code);
                if (64 < strlen($item_code)) {
                    $item_code = mb_strimwidth($item_code, 0, 64);
                }
            }

            $itemInfo['item_code'] = $item_code;
            $itemInfo['item_name'] = $orderItem->getProductName().'x'.$orderItem->getQuantity().'個（代表）';

            return $itemInfo;
        }
    }
}
