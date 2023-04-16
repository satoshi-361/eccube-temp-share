<?php
namespace Plugin\JsysAsi\Entity;

use Eccube\Entity\Master\AbstractMasterEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ログイン履歴ステータスマスタEntity
 * @author manabe
 *
 * @ORM\Table(name="plg_jsys_asi_login_history_status")
 * @ORM\Entity(
 *   repositoryClass="Plugin\JsysAsi\Repository\JsysAsiLoginHistoryStatusRepository"
 * )
 */
class JsysAsiLoginHistoryStatus extends AbstractMasterEntity
{
    /**
     * 1:成功
     * @var int
     */
    const SUCCESS = 1;

    /**
     * 2:拒否
     * @var int
     */
    const DENY = 2;

    /**
     * 9:失敗
     * @var int
     */
    const FAILURE = 9;

}
