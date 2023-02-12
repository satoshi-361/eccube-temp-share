<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_my_page_regular_setting")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\MyPageRegularSettingRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class MyPageRegularSetting extends AbstractMasterEntity
{
    const REGULAR_CYCLE = 1;
    const NEXT_DELIVERY_DATE = 2;
    const NUMBER_OR_ITEMS = 3;
    const CANCELLATION = 4;
    const SUSPEND_AND_RESUME = 5;
    const SKIP_ONCE = 6;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\Config", mappedBy="myPageRegularSettings")
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
}
