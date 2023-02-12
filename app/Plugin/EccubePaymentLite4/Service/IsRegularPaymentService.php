<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Entity\Order;
use Eccube\Service\Payment\Method\Cash;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;

class IsRegularPaymentService
{
    public function isRegularPayment(Order $Order)
    {
        if (!is_null($Order->getPayment()) &&
            ($Order->getPayment()->getMethodClass() === Credit::class ||
            $Order->getPayment()->getMethodClass() === Reg_Credit::class ||
            $Order->getPayment()->getMethodClass() === Cash::class)) {
            return true;
        }

        return false;
    }
}
