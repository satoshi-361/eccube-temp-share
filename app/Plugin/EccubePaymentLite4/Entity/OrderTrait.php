<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @ORM\Column(name="trans_code", type="string", length=255, nullable=true)
     */
    private $trans_code;

    /**
     * @ORM\Column(name="gmo_epsilon_order_no", type="string", length=255, nullable=true)
     */
    private $gmo_epsilon_order_no;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\PaymentStatus")
     * @ORM\JoinColumn(name="payment_status_id", referencedColumnName="id")
     */
    private $PaymentStatus;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularOrder", inversedBy="Orders")
     * @ORM\JoinColumn(name="regular_order_id", referencedColumnName="id")
     */
    private $RegularOrder;

    public function setTransCode($transCode)
    {
        $this->trans_code = $transCode;

        return $this;
    }

    public function getTransCode()
    {
        return $this->trans_code;
    }

    public function getPaymentStatus()
    {
        return $this->PaymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $PaymentStatus = null)
    {
        $this->PaymentStatus = $PaymentStatus;

        return $this;
    }

    public function getGmoEpsilonOrderNo()
    {
        return $this->gmo_epsilon_order_no;
    }

    public function setGmoEpsilonOrderNo($gmo_epsilon_order_no = null)
    {
        $this->gmo_epsilon_order_no = $gmo_epsilon_order_no;

        return $this;
    }

    public function getRegularOrder()
    {
        return $this->RegularOrder;
    }

    public function setRegularOrder($RegularOrder)
    {
        $this->RegularOrder = $RegularOrder;

        return $this;
    }
}
