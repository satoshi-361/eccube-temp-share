<?php
namespace Plugin\JsysAsi\Entity;

use Eccube\Entity\Master\AbstractMasterEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ログイン履歴2要素認証ステータスマスタEntity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_login_history_tfa_status")
 * @ORM\Entity(
 *   repositoryClass="Plugin\JsysAsi\Repository\JsysAsiLoginHistoryTfaStatusRepository"
 * )
 */
class JsysAsiLoginHistoryTfaStatus extends AbstractMasterEntity
{
    /**
     * 1:機能無効
     * @var int
     */
    const DISABLED = 1;

    /**
     * 2:有効OTP
     * @var int
     */
    const VALID = 2;

    /**
     * 3:無効OTP
     * @var int
     */
    const INVALID = 3;

    /**
     * 4:使用済OTP
     * @var int
     */
    const USED = 4;

}
