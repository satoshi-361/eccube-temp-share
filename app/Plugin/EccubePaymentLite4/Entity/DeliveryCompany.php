<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_delivery_company")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\DeliveryCompanyRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class DeliveryCompany extends AbstractMasterEntity
{
    const SAGAWA = 11;
    const SAGAWA_NAME = '佐川急便';

    const YAMATO = 12;
    const YAMATO_NAME = 'ヤマト運輸・ネコポス・宅急便コンパクト・クロネコDM便';

    const SEINO = 14;
    const SEINO_NAME = '西濃運輸';

    const REGISTERED_MAIL__SPECIFIC_RECORD_MAIL = 15;
    const REGISTERED_MAIL__SPECIFIC_RECORD_MAIL_NAME = '郵便書留・特定記録郵便';

    const YUPACK__EXPACK__POST_PACKET = 16;
    const YUPACK__EXPACK__POST_PACKET_NAME = 'ゆうパック・エクスパック・ポスパケット';

    const FUKUYAMA = 18;
    const FUKUYAMA_NAME = '福山通運';

    const ECOHAI = 27;
    const ECOHAI_NAME = 'エコ配';

    const TEN_FLIGHT__LETTER_PACK__NEW_LIMITED_EXPRESS_MAIL__YU_PACKET = 28;
    const TEN_FLIGHT__LETTER_PACK__NEW_LIMITED_EXPRESS_MAIL__YU_PACKET_NAME = '翌朝10時便・レターパック・新特急郵便・ゆうパケット';
}
