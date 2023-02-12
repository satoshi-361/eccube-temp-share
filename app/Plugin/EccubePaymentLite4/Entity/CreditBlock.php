<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_credit_access_block", indexes={@ORM\Index(name="dtb_epsilon_credit_aceess_block_ip_address_key_idx", columns={"ip_address"})})
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\CreditBlockRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class CreditBlock extends AbstractEntity
{
    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="ip_address", type="string", length=255, nullable=true)
     */
    private $ip_address;

    /**
     * @ORM\Column(name="block_date", type="datetimetz")
     */
    private $block_date;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getIpAddress()
    {
        return $this->ip_address;
    }

    public function setIpAddress($ip_address)
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function getBlockDate()
    {
        return $this->block_date;
    }

    public function setBlockDate($block_date)
    {
        $this->block_date = $block_date;

        return $this;
    }
}
