<?php
namespace Plugin\JsysAsi\Entity;

use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ロック済みIPアドレスEntity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_locked_ip_address", uniqueConstraints={
 *   @ORM\UniqueConstraint(
 *     name="plg_jsys_asi_locked_ip_address_ip_address_key", columns={"ip_address"}
 *   )
 * })
 * @ORM\Entity(
 *   repositoryClass="Plugin\JsysAsi\Repository\JsysAsiLockedIpAddressRepository"
 * )
 */
class JsysAsiLockedIpAddress extends AbstractEntity
{
    /**
     * ID
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * IPアドレス
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=255)
     */
    private $ip_address;

    /**
     * 登録日
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * 更新日
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;


    /**
     * IDを取得します。
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * IPアドレスを設定します。
     * @param string $ipAddress
     * @return \Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;
        return $this;
    }

    /**
     * IPアドレスを取得します。
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * 登録日を設定します。
     * @param \DateTime $createDate
     * @return \Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;
        return $this;
    }

    /**
     * 登録日を取得します。
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * 更新日を設定します。
     * @param \DateTime $updateDate
     * @return \Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;
        return $this;
    }

    /**
     * 更新日を取得します。
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

}
