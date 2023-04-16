<?php
namespace Plugin\JsysAsi\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus;

/**
 * ログイン履歴2要素認証ステータスマスタRepository
 * @author manabe
 *
 */
class JsysAsiLoginHistoryTfaStatusRepository extends AbstractRepository
{
    /**
     * JsysAsiLoginHistoryTfaStatusRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsysAsiLoginHistoryTfaStatus::class);
    }
}
