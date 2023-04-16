<?php
namespace Plugin\JsysAsi\Entity;

use Eccube\Entity\Master\AbstractMasterEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ログイン履歴ロックステータスマスタEntity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_login_history_lock_status")
 * @ORM\Entity(
 *   repositoryClass="Plugin\JsysAsi\Repository\JsysAsiLoginHistoryLockStatusRepository"
 * )
 */
class JsysAsiLoginHistoryLockStatus extends AbstractMasterEntity
{

    /**
     * 1:機能無効
     * @var int
     */
    const DISABLED = 1;

    /**
     * 2:未ロック
     * @var int
     */
    const UNLOCKED = 2;

    /**
     * 3:ロック済み
     * @var int
     */
    const LOCKED = 3;

}
