<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_payment")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\GmoEpsilonPaymentRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class GmoEpsilonPayment extends AbstractMasterEntity
{
    const CREDIT = 1;
    const REGISTERED_CREDIT_CARD = 2;
    const CONVENIENCE_STORE = 3;
    const ONLINE_BANK_JAPAN_NET_BANK = 4;
    const ONLINE_BANK_RAKUTEN = 5;
    const PAY_EASY = 7;
    const WEB_MONEY = 8;
    const YAHOO_WALLET = 9;
    const PAYPAL = 11;
    const BIT_CASH = 12;
    const CHOCOM_E_MONEY = 13;
    const SMARTPHONE_CARRIER = 15;
    const JCB_PREMO = 16;
    const ONLINE_BANK_SUMISHIN_SBI = 17;
    const GMO_DEFERRED_PAYMENT = 18;
    const VIRTUAL_ACCOUNT = 22;
    const PAYPAY = 25;
    const MAIL_LINK = 99;

    /**
     * @var int
     * @ORM\Column(name="charge", type="decimal", precision=12, scale=2, nullable=true, options={"unsigned":true,"default":0})
     */
    private $charge = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rule_max", type="decimal", precision=12, scale=2, nullable=true, options={"unsigned":true})
     */
    private $rule_max;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rule_min", type="decimal", precision=12, scale=2, nullable=true, options={"unsigned":true})
     */
    private $rule_min;

    /**
     * @var string|null
     *
     * @ORM\Column(name="method_class", type="string", length=255, nullable=true)
     */
    private $method_class;
    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\Config", mappedBy="gmoEpsilonPayments")
     */
    private $gmoEpsilonConfigs;

    public function __construct()
    {
        $this->gmoEpsilonConfigs = new ArrayCollection();
    }

    public function addGmoEpsilonConfig(Config $Config)
    {
        $this->gmoEpsilonConfigs[] = $Config;

        return $this;
    }

    /**
     * @return int
     */
    public function getCharge()
    {
        return $this->charge;
    }

    /**
     * @return self
     */
    public function setCharge(int $charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRuleMax()
    {
        return $this->rule_max;
    }

    /**
     * @return self
     */
    public function setRuleMax(?string $rule_max)
    {
        $this->rule_max = $rule_max;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRuleMin()
    {
        return $this->rule_min;
    }

    /**
     * @return self
     */
    public function setRuleMin(?string $rule_min)
    {
        $this->rule_min = $rule_min;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMethodClass()
    {
        return $this->method_class;
    }

    /**
     * @return self
     */
    public function setMethodClass(?string $method_class)
    {
        $this->method_class = $method_class;

        return $this;
    }
}
