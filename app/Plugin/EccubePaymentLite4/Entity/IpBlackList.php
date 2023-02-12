<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_ip_black_list")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\IpBlackListRepository")
 */
class IpBlackList extends AbstractEntity
{
    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\Config", mappedBy="ipBlackList")
     */
    private $gmoEpsilonConfigs;

    /**
     * @ORM\Column(name="ip_address", type="string", length=255)
     */
    private $ip_address;

    /**
     * @ORM\Column(name="sort_no", type="smallint", options={"unsigned":true})
     */
    protected $sort_no;

    /**
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    public function __construct()
    {
        $this->gmoEpsilonConfigs = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addGmoEpsilonConfig(Config $Config)
    {
        $this->gmoEpsilonConfigs[] = $Config;

        return $this;
    }

    public function getIpAddress()
    {
        return $this->ip_address;
    }

    public function setIpAddress(string $ip_address)
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function setSortNo($sort_no)
    {
        $this->sort_no = $sort_no;

        return $this;
    }

    public function getSortNo()
    {
        return $this->sort_no;
    }

    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }

    public function __toString()
    {
        return (string) $this->ip_address;
    }
}
