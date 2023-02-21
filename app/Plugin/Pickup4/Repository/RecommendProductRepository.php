<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Pickup4\Repository;

use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\AbstractRepository;
use Plugin\Pickup4\Entity\RecommendProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Plugin\CustomerReview4\Entity\CustomerReviewTotal;
use Plugin\CustomerReview4\Repository\CustomerReviewTotalRepository;

/**
 * RecommendProductRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RecommendProductRepository extends AbstractRepository
{
    /**
     * @var CustomerReviewTotalRepository
     */
    protected $customerReviewTotalRepository;

    /**
     * CouponRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param CustomerReviewTotalRepository $customerReviewTotalRepository
     */
    public function __construct(RegistryInterface $registry, CustomerReviewTotalRepository $customerReviewTotalRepository)
    {
        parent::__construct($registry, RecommendProduct::class);
        $this->customerReviewTotalRepository = $customerReviewTotalRepository;
    }

    /**
     * Find list.
     *
     * @return mixed
     */
    public function getRecommendList()
    {
        $qb = $this->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p');
        $qb->where('rp.visible = true');
        $qb->addOrderBy('rp.sort_no', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get max rank.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxRank()
    {
        $qb = $this->createQueryBuilder('rp')
            ->select('MAX(rp.sort_no) AS max_rank');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get recommend product by display status of product.
     *
     * @return array
     */
    public function getRecommendProduct( $limit = 5 )
    {
        $query = $this->createQueryBuilder('rp')
            ->innerJoin('Eccube\Entity\Product', 'p', 'WITH', 'p.id = rp.Product')
            ->where('p.Status = :Disp')
            ->andWhere('p.launch_date <= :today')
            ->andWhere('rp.visible = true')
            ->orderBy('rp.sort_no', 'DESC')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->setParameter('today', new \DateTime())
            ->setFirstResult(0)
            ->setMaxResults($limit)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Get recommend product by parameters.
     * 
     * @param integer $offset
     * @param integer $limit
     *
     * @return array
     */
    public function getPickupsByParameters( $offset = 0, $limit = 5)
    {
        $qb = $this->createQueryBuilder('rp')
            ->innerJoin('Eccube\Entity\Product', 'p', 'WITH', 'p.id = rp.Product')
            ->where('p.Status = :Disp')
            ->andWhere('p.launch_date <= :today')
            ->andWhere('rp.visible = true')
            ->orderBy('rp.sort_no', 'DESC')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->setParameter('today', new \DateTime())
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        // $CustomerReviewTotalRepository = $this->getDoctrine()->getRepository(CustomerReviewTotal::class);
        $result = [];

        foreach ($qb->getQuery()->getResult() as $Pickup) {
            $Product = $Pickup->getProduct();
            $Categories = [];
            foreach ( $Product->getProductCategories() as $ProductCategory ) {
                $Categories[] = $ProductCategory->getCategory()->getName();
            }

            $reviewList = $this->customerReviewTotalRepository->getRecommend($Product->getId());
            $reviewerTotal = $reviewTotalPoint = 0;
            $count = 5;

            foreach ( $reviewList as $review ) {
                $reviewerTotal += $review;
                $reviewTotalPoint += $review * $count;
                $count -= 1;
            }

            $resultItem = [
                'id' => $Product->getId(),
                'image' => $Product->getMainFileName()->getFileName(),
                'name' => $Product->getName(),
                'price' => $Product->getPrice02IncTaxMax(),
                'affiliate' => $Product->getAffiliateReward(),
                'editor_image' => $Product->getCustomer()->getImage(),
                'nick_name' => $Product->getCustomer()->getNickName(),
                'launch_date' => $Product->getLaunchDate()->format('Y年m月d日'),
                'stock' => $Product->getStockMax(),
                'categories' => array_reverse($Categories),
                'review_point' => $reviewerTotal == 0 ? 0 : number_format(( $reviewTotalPoint / $reviewerTotal ), 1, '.', ''),
                'reviewer_total' => $reviewerTotal,
            ];
            $result[] = $resultItem;
        }

        return $result;
    }

    /**
     * Number of recommend.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countRecommend()
    {
        $qb = $this->createQueryBuilder('rp');
        $qb->select('COUNT(rp)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Move rank.
     *
     * @param array $arrRank
     *
     * @return array
     *
     * @throws \Exception
     */
    public function moveRecommendRank(array $arrRank)
    {
        $this->getEntityManager()->beginTransaction();
        $arrRankMoved = [];
        try {
            foreach ($arrRank as $recommendId => $rank) {
                /* @var $Recommend RecommendProduct */
                $Recommend = $this->find($recommendId);
                if ($Recommend->getSortno() == $rank) {
                    continue;
                }
                $arrRankMoved[$recommendId] = $rank;
                $Recommend->setSortno($rank);
                $this->getEntityManager()->persist($Recommend);
            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }

        return $arrRankMoved;
    }

    /**
     * Save recommend.
     *
     * @param RecommendProduct $RecommendProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function saveRecommend(RecommendProduct $RecommendProduct)
    {
        $this->getEntityManager()->beginTransaction();
        try {
            $this->getEntityManager()->persist($RecommendProduct);
            $this->getEntityManager()->flush($RecommendProduct);
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Get all id of recommend product.
     *
     * @return array
     */
    public function getRecommendProductIdAll()
    {
        $query = $this->createQueryBuilder('rp')
            ->select('IDENTITY(rp.Product) as id')
            ->where('rp.visible = true')
            ->getQuery();
        $arrReturn = $query->getScalarResult();

        return array_map('current', $arrReturn);
    }

    /**
     * ピックアップ商品情報を削除する
     *
     * @param RecommendProduct $RecommendProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteRecommend(RecommendProduct $RecommendProduct)
    {
        // ピックアップ商品情報を書き換える
        $RecommendProduct->setVisible(false);

        // ピックアップ商品情報を登録する
        return $this->saveRecommend($RecommendProduct);
    }
}
