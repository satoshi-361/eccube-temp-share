<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_regular_cycle_type")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\RegularCycleTypeRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class RegularCycleType extends AbstractMasterEntity
{
    const REGULAR_DAILY_CYCLE = 1;
    const REGULAR_WEEKLY_CYCLE = 2;
    const REGULAR_MONTHLY_CYCLE = 3;
    const REGULAR_SPECIFIC_DAY_CYCLE = 4;
    const REGULAR_SPECIFIC_WEEK_CYCLE = 5;
}
