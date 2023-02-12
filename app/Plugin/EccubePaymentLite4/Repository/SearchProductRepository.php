<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Common\EccubeConfig;
use Eccube\Doctrine\Query\Queries;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\QueryKey;
use Eccube\Util\StringUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SearchProductRepository extends ProductRepository
{
    /**
     * @var Queries
     */
    protected $queries;

    public function __construct(RegistryInterface $registry, Queries $queries, EccubeConfig $eccubeConfig)
    {
        parent::__construct($registry, $queries, $eccubeConfig);
        $this->queries = $queries;
    }

    /**
     * Search regular product
     *
     * @param array $searchData
     * @param $SaleType
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getQueryBuilderBySearchDataProductForAdmin($searchData, $SaleType)
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('pc', 'pi', 'tr', 'ps')
            ->innerJoin('p.ProductClasses', 'pc')
            ->leftJoin('p.ProductImage', 'pi')
            ->leftJoin('pc.TaxRule', 'tr')
            ->leftJoin('pc.ProductStock', 'ps')
            ->andWhere('pc.visible = :visible')
            ->setParameter('visible', true)
            ->andWhere('pc.SaleType = :SaleType')
            ->setParameter('SaleType', $SaleType);

        // id
        if (isset($searchData['id']) && StringUtil::isNotBlank($searchData['id'])) {
            $id = preg_match('/^\d{0,10}$/', $searchData['id']) ? $searchData['id'] : null;
            if ($id && $id > '2147483647' && $this->isPostgreSQL()) {
                $id = null;
            }
            $qb
                ->andWhere('p.id = :id OR p.name LIKE :likeid OR pc.code LIKE :likeid')
                ->setParameter('id', $id)
                ->setParameter('likeid', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $searchData['id']).'%');
        }

        // category
        if (!empty($searchData['category_id']) && $searchData['category_id']) {
            $Categories = $searchData['category_id']->getSelfAndDescendants();
            if ($Categories) {
                $qb
                    ->innerJoin('p.ProductCategories', 'pct')
                    ->innerJoin('pct.Category', 'c')
                    ->andWhere($qb->expr()->in('pct.Category', ':Categories'))
                    ->setParameter('Categories', $Categories);
            }
        }

        // Order By
        $qb
            ->orderBy('p.update_date', 'DESC');

        return $this->queries->customize(QueryKey::PRODUCT_SEARCH_ADMIN, $qb, $searchData);
    }
}
