<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * RegularStatus
 *
 * @ORM\Table(name="plg_eccube_payment_lite4_regular_status")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\RegularStatusRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class RegularStatus extends AbstractMasterEntity
{
    /** 継続 */
    const CONTINUE = 1;
    /** 解約 */
    const CANCELLATION = 2;
    /** 休止 */
    const SUSPEND = 3;
    /** 決済エラー */
    const PAYMENT_ERROR = 4;
    /** システムエラー */
    const SYSTEM_ERROR = 5;
    /** 再決済待ち */
    const WAITING_RE_PAYMENT = 6;
    /** 解約（再開可能期限切れ） */
    const CANCELLATION_EXPIRED_RESUMPTION = 7;

    const CONTINUE_NAME = '継続';
    const CANCELLATION_NAME = '解約';
    const SUSPEND_NAME = '休止';
    const PAYMENT_ERROR_NAME = '決済エラー';
    const SYSTEM_ERROR_NAME = 'システムエラー';
    const WAITING_RE_PAYMENT_NAME = '再決済待ち';
    const CANCELLATION_EXPIRED_RESUMPTION_NAME = '解約（再開可能期限切れ）';
}
