<?php

namespace Plugin\CustomerReview4\Repository;

use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Repository\QueryKey;
use Plugin\CustomerReview4\Entity\CustomerReviewTotal;
use Doctrine\ORM\QueryBuilder;

class ProductOrderByUpdate implements QueryCustomizer
{
    /**
     * クエリをカスタマイズします。
     *
     * @param QueryBuilder $builder
     * @param array $params
     * @param string $queryKey
     */
    public function customize(QueryBuilder $builder, $params, $queryKey)
    {
        if(!empty($params["orderby"]) && $params["orderby"]->getId() == CustomerReviewTotal::SORT_RECOMMEND_HIGHER) {
            // レビューの高い順にソート
            $builder->addSelect('CASE WHEN rmt.recommend_avg is NULL THEN 0 ELSE rmt.recommend_avg END as HIDDEN recommend_sort_base');
            $builder->leftJoin('Plugin\CustomerReview4\Entity\CustomerReviewTotal', 'rmt', 'WITH', 'rmt.Product = p.id');
            $builder->OrderBy('recommend_sort_base', 'DESC');
            $builder->addOrderBy('p.create_date', 'DESC');
            $builder->addOrderBy('p.id', 'DESC');
        } else if(!empty($params["orderby"]) && $params["orderby"]->getId() == CustomerReviewTotal::SORT_RECOMMEND_OUTNUMBER) {
            // レビューの多い順＞レビューの高い順にソート
            $builder->addSelect('CASE WHEN rmt.recommend_avg is NULL THEN 0 ELSE rmt.recommend_avg END as HIDDEN recommend_sort_avg');
            $builder->addSelect('CASE WHEN rmt.reviewer_num is NULL THEN 0 ELSE rmt.reviewer_num END as HIDDEN recommend_sort_num');
            $builder->leftJoin('Plugin\CustomerReview4\Entity\CustomerReviewTotal', 'rmt', 'WITH', 'rmt.Product = p.id');
            $builder->OrderBy('recommend_sort_num', 'DESC');
            $builder->addOrderBy('recommend_sort_avg', 'DESC');
            $builder->addOrderBy('p.create_date', 'DESC');
            $builder->addOrderBy('p.id', 'DESC');
        }
    }

    /**
     * カスタマイズ対象のキーを返します。
     *
     * @return string
     */
    public function getQueryKey(): string
    {
        return QueryKey::PRODUCT_SEARCH;
    }
}