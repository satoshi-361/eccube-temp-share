<?php
namespace Plugin\JsysAsi\Entity;

use Eccube\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * 2要素認証OTP履歴Entity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_tfa_otp_history", indexes={
 *   @ORM\Index(name="plg_jsys_asi_tfa_otp_history_tfa_user_id_idx", columns={
 *     "tfa_user_id",
 *     "create_date",
 *     "otp"
 *   }),
 *   @ORM\Index(name="plg_jsys_asi_tfa_otp_history_create_date_idx", columns={
 *     "create_date"
 *   })
 * })
 * @ORM\Entity(
 *   repositoryClass="Plugin\JsysAsi\Repository\JsysAsiTfaOtpHistoryRepository"
 * )
 */
class JsysAsiTfaOtpHistory extends AbstractEntity
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
     * 2要素認証ユーザーID
     * @var int
     *
     * @ORM\Column(name="tfa_user_id", type="integer", options={"unsigned":true})
     */
    private $tfa_user_id;

    /**
     * OTP
     * @var string
     *
     * @ORM\Column(name="otp", type="string", length=100)
     */
    private $otp;

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
     * 2要素認証ユーザーIDを設定します。
     * @param int $tfa_user_id
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory
     */
    public function setTfaUserId($tfaUserId)
    {
        $this->tfa_user_id = $tfaUserId;
        return $this;
    }

    /**
     * 2要素認証ユーザーIDを取得します。
     * @return int
     */
    public function getTfaUserId()
    {
        return $this->tfa_user_id;
    }

    /**
     * OTPを設定します。
     * @param string $otp
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory
     */
    public function setOtp($otp)
    {
        $this->otp = $otp;
        return $this;
    }

    /**
     * OTPを取得します。
     * @return string
     */
    public function getOtp()
    {
        return $this->otp;
    }

    /**
     * 登録日を設定します。
     * @param \DateTime $createDate
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory
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
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory
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
