<?php

namespace Plugin\EccubePaymentLite4\Service;

class GetCardExpireDateTimeService
{
    public function get(string $cardExpire): \DateTime
    {
        $arr = explode('/', $cardExpire);
        $cardExpireYear = $arr[0];
        $cardExpireMonth = $arr[1];

        return  new \DateTime('last day of '.$cardExpireYear.'-'.$cardExpireMonth);
    }
}
