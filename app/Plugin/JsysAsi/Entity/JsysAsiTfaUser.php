<?php
namespace Plugin\JsysAsi\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * 2要素認証ユーザーEntity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_tfa_user", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="plg_jsys_asi_tfa_user_member_key", columns={
 *     "member_id"
 *   })
 * })
 * @ORM\Entity(repositoryClass="Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository")
 */
class JsysAsiTfaUser extends AbstractEntity
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
     * メンバーID
     * @var int
     *
     * @ORM\Column(name="member_id", type="integer", options={"unsigned":true})
     */
    private $member_id;

    /**
     * 有効
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", options={
     *   "default":false
     * })
     */
    private $enabled;

    /**
     * シークレット
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=255)
     */
    private $secret;

    /**
     * シークレットパスワード
     * @var string
     *
     * @ORM\Column(name="secret_password", type="string", length=255)
     */
    private $secret_password;

    /**
     * シークレットSalt
     * @var string
     *
     * @ORM\Column(name="secret_salt", type="string", length=255)
     */
    private $secret_salt;

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
     * メンバーIDを設定します。
     * @param int $memberId
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    public function setMemberId($memberId)
    {
        $this->member_id = $memberId;
        return $this;
    }

    /**
     * メンバーIDを取得します。
     * @return int
     */
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * 有効・無効を設定します。
     * @param boolean $enabled
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * 有効・無効を取得します。
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * シークレットを設定します。
     * @param string $secret
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * シークレットを取得します。
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * シークレットパスワードを設定します。
     * @param string $secretPassword
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    public function setSecretPassword($secretPassword)
    {
        $this->secret_password = $secretPassword;
        return $this;
    }

    /**
     * シークレットパスワードを取得します。
     * @return string
     */
    public function getSecretPassword()
    {
        return $this->secret_password;
    }

    /**
     * シークレットSaltを設定します。
     * @param string $secretSalt
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    public function setSecretSalt($secretSalt)
    {
        $this->secret_salt = $secretSalt;
        return $this;
    }

    /**
     * シークレットSaltを取得します。
     * @return string
     */
    public function getSecretSalt()
    {
        return $this->secret_salt;
    }

    /**
     * 登録日を設定します。
     * @param \DateTime $createDate
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
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
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
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
