<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="plg_eccube_payment_lite4_credit_access_log", indexes={
 *     @ORM\Index(name="dtb_epsilon_credit_access_logs_ip_address_key_idx", columns={"ip_address"})
 *  }
 * )
 */
class CreditAccessLog extends AbstractEntity
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
     * @ORM\Column(name="access_date", type="datetimetz")
     */
    private $access_date;

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

    public function getAccessDate()
    {
        return $this->access_date;
    }

    public function setAccessDate($access_date)
    {
        $this->access_date = $access_date;

        return $this;
    }
}
