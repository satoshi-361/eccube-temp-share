<?php
namespace Plugin\JsysAsi\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Eccube\Util\StringUtil;
use Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress;

/**
 * ロック済みIPアドレスRepository
 * @author manabe
 *
 */
class JsysAsiLockedIpAddressRepository extends AbstractRepository
{
    /**
     * JsysAsiLockedIpAddressRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsysAsiLockedIpAddress::class);
    }

    /**
     * 抽出条件を適用したQueryBuilderを取得します。
     * @param array $searchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData($searchData)
    {
        $qb = $this->createQueryBuilder('l')->select('l');

        // IPアドレス
        if (isset($searchData['ip_address'])) {
            $ip = $searchData['ip_address'];
            if (StringUtil::isNotBlank($ip)) {
                $where = 'l.ip_address LIKE :ip_address';
                $value = '%' . str_replace('%', '\\%', $ip) . '%';
                $qb
                    ->andWhere($where)
                    ->setParameter(':ip_address', $value);
            }
        }

        $qb->addOrderBy('l.id');

        return $qb;
    }

}
