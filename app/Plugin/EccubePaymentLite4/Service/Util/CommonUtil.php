<?php

namespace Plugin\EccubePaymentLite4\Service\Util;

use Eccube\Entity\Master\RoundingType;

class CommonUtil
{
    public static function roundByCalcRule($value, $calcRule)
    {
        switch ($calcRule) {
            // 四捨五入
            case RoundingType::ROUND:
                $ret = round($value);
                break;
            // 切り捨て
            case RoundingType::FLOOR:
                $ret = floor(strval($value));
                break;
            // 切り上げ
            // デフォルト:切り上げ
            default:
                $ret = ceil($value);
                break;
        }

        return $ret;
    }
}
