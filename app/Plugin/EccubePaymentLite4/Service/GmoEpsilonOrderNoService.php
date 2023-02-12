<?php

namespace Plugin\EccubePaymentLite4\Service;

class GmoEpsilonOrderNoService
{
    public function create(int $orderId): string
    {
        return $orderId.'x'.date('YmdHis');
    }

    /**
     * レスポンスパラメータorder_numberから受注番号を取得
     *
     * 受注番号xリクエスト日時 (ex. 12345x20190301)
     *
     * @param $order_number
     *
     * @return integer
     */
    public function get($order_number)
    {
        if (empty($order_number)) {
            return null;
        } elseif (is_numeric($order_number)) {
            return $order_number;
        } else {
            \preg_match('/(\d+)x(\d+)/', $order_number, $matches);

            return $matches[1];
        }
    }
}
