<?php
namespace Plugin\JsysAsi\Entity;

use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ログイン履歴Entity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_login_history", indexes={
 *   @ORM\Index(name="plg_jsys_asi_login_history_login_date_idx", columns={
 *     "login_date"
 *   }),
 *   @ORM\Index(name="plg_jsys_asi_login_history_login_id_idx", columns={
 *     "login_id"
 *   }),
 *   @ORM\Index(name="plg_jsys_asi_login_history_ip_address_idx", columns={
 *     "ip_address"
 *   }),
 *   @ORM\Index(name="plg_jsys_asi_login_history_status_idx", columns={
 *     "status"
 *   })
 * })
 * @ORM\Entity(
 *   repositoryClass="Plugin\JsysAsi\Repository\JsysAsiLoginHistoryRepository"
 * )
 */
class JsysAsiLoginHistory extends AbstractEntity
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
     * ログイン日時
     * @var \DateTime
     *
     * @ORM\Column(name="login_date", type="datetimetz")
     */
    private $login_date;

    /**
     * ログインID
     * @var string
     *
     * @ORM\Column(name="login_id", type="string", length=255)
     */
    private $login_id;

    /**
     * IPアドレス
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=255)
     */
    private $ip_address;

    /**
     * ステータス
     * @var JsysAsiLoginHistoryStatus
     *
     * @ORM\ManyToOne(
     *   targetEntity="Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus"
     * )
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status", referencedColumnName="id")
     * })
     */
    private $Status;

    /**
     * 2要素認証ステータス
     * @var JsysAsiLoginHistoryTfaStatus
     *
     * @ORM\ManyToOne(
     *   targetEntity="Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus"
     * )
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tfa_status", referencedColumnName="id")
     * })
     */
    private $TfaStatus;

    /**
     * ロックステータス
     * @var JsysAsiLoginHistoryLockStatus
     *
     * @ORM\ManyToOne(
     *   targetEntity="Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus"
     * )
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="lock_status", referencedColumnName="id")
     * })
     */
    private $LockStatus;

    /**
     * ユーザーエージェント
     * @var string
     *
     * @ORM\Column(name="user_agent", type="string", length=500)
     */
    private $user_agent;

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
     * ログイン日時を設定します。
     * @param \DateTime $loginDate
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
     */
    public function setLoginDate($loginDate)
    {
        $this->login_date = $loginDate;
        return $this;
    }

    /**
     * ログイン日時を取得します。
     * @return \DateTime
     */
    public function getLoginDate()
    {
        return $this->login_date;
    }

    /**
     * ログインIDを設定します。
     * @param string $loginId
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
     */
    public function setLoginId($loginId)
    {
        $this->login_id = $loginId;
        return $this;
    }

    /**
     * ログインIDを取得します。
     * @return string
     */
    public function getLoginId()
    {
        return $this->login_id;
    }

    /**
     * IPアドレスを設定します。
     * @param string $ipAddress
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
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
     * ステータスを設定します。
     * @param JsysAsiLoginHistoryStatus $Status
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
     */
    public function setStatus(JsysAsiLoginHistoryStatus $Status)
    {
        $this->Status = $Status;
        return $this;
    }

    /**
     * ステータスを取得します。
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * 2要素認証ステータスを設定します。
     * @param JsysAsiLoginHistoryTfaStatus $TfaStatus
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
     */
    public function setTfaStatus(JsysAsiLoginHistoryTfaStatus $TfaStatus)
    {
        $this->TfaStatus = $TfaStatus;
        return $this;
    }

    /**
     * 2要素認証ステータスを取得します。
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus
     */
    public function getTfaStatus()
    {
        return $this->TfaStatus;
    }

    /**
     * ロックステータスを設定します。
     * @param JsysAsiLoginHistoryLockStatus $LockStatus
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
     */
    public function setLockStatus(JsysAsiLoginHistoryLockStatus $LockStatus)
    {
        $this->LockStatus = $LockStatus;
        return $this;
    }

    /**
     * ロックステータスを取得します。
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus
     */
    public function getLockStatus()
    {
        return $this->LockStatus;
    }

    /**
     * ユーザーエージェントを設定します。
     * @param string $userAgent
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
     */
    public function setUserAgent($userAgent)
    {
        $this->user_agent = $userAgent;
        return $this;
    }

    /**
     * ユーザーエージェントを取得します。
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * 登録日を設定します。
     * @param \DateTime $createDate
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
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
     * @return \Plugin\JsysAsi\Entity\JsysAsiLoginHistory
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
