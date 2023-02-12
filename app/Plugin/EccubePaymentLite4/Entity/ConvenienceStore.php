<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_convenience_store")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\ConvenienceStoreRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ConvenienceStore extends AbstractMasterEntity
{
    const SEVEN_ELEVEN = 11;
    const FAMILY_MART = 21;
    const LAWSON = 31;
    const SEICO_MART = 32;
    const MINI_STOP = 33;

    const SEVEN_ELEVEN_NAME = 'セブンイレブン';
    const FAMILY_MART_NAME = 'ファミリーマート';
    const LAWSON_NAME = 'ローソン';
    const SEICO_MART_NAME = 'セイコーマート';
    const MINI_STOP_NAME = 'ミニストップ';

    /**
     * @ORM\Column(name="conveni_code", type="smallint", options={"unsigned":true})
     */
    private $conveni_code;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\Config", mappedBy="ConvenienceStores")
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

    public function getConveniCode()
    {
        return $this->conveni_code;
    }

    public function setConveniCode($conveni_code): self
    {
        $this->conveni_code = $conveni_code;

        return $this;
    }
}
