<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Delivery")
 */
trait DeliveryTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\DeliveryCompany")
     */
    private $DeliveryCompany;

    public function getDeliveryCompany()
    {
        return $this->DeliveryCompany;
    }

    public function setDeliveryCompany(DeliveryCompany $deliveryCompany)
    {
        $this->DeliveryCompany = $deliveryCompany;

        return $this;
    }
}
