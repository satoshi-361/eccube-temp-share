<?php
namespace Plugin\JsysAsi\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;

/**
 * 2要素認証ユーザーRepository
 * @author manabe
 *
 */
class JsysAsiTfaUserRepository extends AbstractRepository
{
    /**
     * JsysAsiTfaUserRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsysAsiTfaUser::class);
    }

}
