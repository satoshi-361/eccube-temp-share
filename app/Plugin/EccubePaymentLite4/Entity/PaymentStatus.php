<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_payment_status")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class PaymentStatus extends AbstractMasterEntity
{
    /**
     * 未課金
     * 決済がまだ完了していない状態、
     * または決済を最後まで完了せずに決済をやめた状態、
     * およびなんらかの原因で決済登録が失敗している可能性がある状態
     */
    const UNPAID = 0;

    /**
     * 課金済み
     * 購入者による支払いが完了した状態
     */
    const CHARGED = 1;

    /**
     * 審査中
     * GMO後払いにおいて、リアルタイム与信で審査ができなかった状態
     */
    const UNDER_REVIEW = 4;

    /**
     * 仮売上
     * クレジットカードの与信審査がOKとなった状態
     */
    const TEMPORARY_SALES = 5;

    /**
     * 出荷登録中
     * GMO後払いにおいて、出荷登録が完了した状態
     */
    const SHIPPING_REGISTRATION = 6;

    /** キャンセル */
    const CANCEL = 9;

    /**
     * 審査NG
     * GMO後払いにおいて、審査が通らなかった状態
     */
    const EXAMINATION_NG = 11;
}
