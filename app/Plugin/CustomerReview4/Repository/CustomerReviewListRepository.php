<?php

namespace Plugin\CustomerReview4\Repository;

use Eccube\Util\StringUtil;
use Eccube\Repository\AbstractRepository;
use Plugin\CustomerReview4\Entity\CustomerReviewList;
use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerReviewListRepository extends AbstractRepository
{
    /**
     * CustomerReviewListRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerReviewList::class);
    }

    /**
     * @param int $id
     * @return null|CustomerReviewList
     */
    public function get($id)
    {
        return $this->find($id);
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData($searchData)
    {
        $qb = $this->createQueryBuilder('r')
                ->andWhere('r.Status = :status' )
                ->setParameter('status', CustomerReviewStatus::SHOW);

        // 商品
        if (!empty($searchData['product_id']) && $searchData['product_id']) {
            $qb
                ->andWhere('r.Product = :product_id' )
                ->setParameter('product_id', $searchData['product_id']);
        }

        // 評価
        $search_recommend_level = false;
        if (!empty($searchData['star']) && $searchData['star']) {
            $qb->andWhere('r.recommend_level = :recommend_level' )
                ->setParameter('recommend_level', $searchData['star']);
            $search_recommend_level = true;
        }

        // Order By
        if (!empty($searchData['orderby']) && $searchData['orderby'] == CustomerReviewList::POST_OLDER ) {
        // 投稿日が古い順
            $qb->OrderBy('r.create_date', 'ASC');
        } else if (!$search_recommend_level && !empty($searchData['orderby']) && $searchData['orderby'] == CustomerReviewList::RECOMMEND_HIGHER ) {
        // 評価が高い順
            $qb->OrderBy('r.recommend_level', 'DESC');
            $qb->addOrderBy('r.create_date', 'DESC');
        } else if (!$search_recommend_level && !empty($searchData['orderby']) && $searchData['orderby'] == CustomerReviewList::RECOMMEND_LOWER ) {
        // 評価が低い順
            $qb->OrderBy('r.recommend_level', 'ASC');
            $qb->addOrderBy('r.create_date', 'DESC');
        } else {
        // 投稿日が新しい順
            $qb->OrderBy('r.create_date', 'DESC');
        }

        return $qb;
    }

    /**
     * @param int $product_id
     * @param int $limit
     * @return null|CustomerReviewList
     */
    public function getReviewList($product_id, $limit = 0)
    {
        $searchData['product_id'] = $product_id;
        $qb = $this->getQueryBuilderBySearchData($searchData);
        if ( is_numeric($limit) && $limit != 0 )
        {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('p')
            ->innerJoin('r.Product', 'p');

        // 商品
        if (!empty($searchData['product_id']) && $searchData['product_id']) {
            $qb
                ->andWhere('r.Product = :product_id' )
                ->setParameter('product_id', $searchData['product_id']);
        }

        // 投稿ユーザ
        if (!empty($searchData['customer_id']) && $searchData['customer_id']) {
            $qb
                ->andWhere('r.Customer = :customer_id' )
                ->setParameter('customer_id', $searchData['customer_id']);
        }

        // ステータス
        if (!empty($searchData['status']) && count($searchData['status']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('r.Status', ':Status'))
                ->setParameter('Status', $searchData['status']);
        }

        // crate_date
        if (isset($searchData['create_date_start']) && !is_null($searchData['create_date_start'])) {
            $date = $searchData['create_date_start'];
            $qb
                ->andWhere('r.create_date >= :create_date_start')
                ->setParameter('create_date_start', $date);
        }

        if (isset($searchData['create_date_end']) && !is_null($searchData['create_date_end'])) {
            $date = clone $searchData['create_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('r.create_date < :create_date_end')
                ->setParameter('create_date_end', $date);
        }

        // Order By
        $qb->orderBy('r.create_date', 'DESC');

        return $qb;
    }
}
