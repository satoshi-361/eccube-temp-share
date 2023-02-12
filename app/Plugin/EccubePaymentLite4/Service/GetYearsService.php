<?php

namespace Plugin\EccubePaymentLite4\Service;

use DateTime;

class GetYearsService
{
    public function get($years)
    {
        $DateTime = new DateTime('today');
        $thisYear = (int) $DateTime->format('Y');
        $arr = [];
        for ($i = 0; $i < $years; $i++) {
            $arr[$thisYear.'年'] = $thisYear;
            $thisYear++;
        }

        return $arr;
    }
}
