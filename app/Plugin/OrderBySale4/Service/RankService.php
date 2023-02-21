<?php

/*
 * This file is part of OrderBySale4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderBySale4\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductRepository;
use Plugin\OrderBySale4\Entity\Config;
use Plugin\OrderBySale4\Repository\ConfigRepository;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\CustomerReview4\Entity\CustomerReviewTotal;

class RankService
{
    private $entityManager;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var BaseInfoRepository
     */
    private $baseInfoRepository;

    /**
     * ProductController constructor.
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        ConfigRepository $configRepository,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->entityManager = $em;
        $this->productRepository = $productRepository;
        $this->configRepository = $configRepository;
        $this->baseInfoRepository = $baseInfoRepository;
    }

    public function getRanks( $limit = 5 )
    {
        $originOptionNostockHidden = $this->entityManager->getFilters()->isEnabled('option_nostock_hidden');

        // Doctrine SQLFilter
        if ($this->baseInfoRepository->get()->isOptionNostockHidden()) {
            $this->entityManager->getFilters()->enable('option_nostock_hidden');
        }

        $qb = $this->productRepository
            ->createQueryBuilder('p');
        $qb
            ->where('p.Status = :Disp')
            ->andWhere('p.launch_date <= :today')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->setParameter('today', new \DateTime())
            ->setFirstResult(0)
            ->setMaxResults($limit);

        /* @var $Config Config */
        $Config = $this->configRepository->findOneBy([]);

        // @see https://github.com/EC-CUBE/ec-cube/issues/1998
        if ($this->entityManager->getFilters()->isEnabled('option_nostock_hidden') == true) {
            $qb->innerJoin('p.ProductClasses', 'pc');
            $qb->andWhere('pc.visible = true');
        }

        $excludes = [OrderStatus::CANCEL, OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::RETURNED];

        if ($Config) {
            if ($Config->getType() == Config::ORDER_BY_AMOUNT) {
                $select = '(SELECT CASE WHEN SUM(oi.price * oi.quantity) IS NULL THEN 1 ELSE SUM(oi.price * oi.quantity + 1) END';
                // $select = '(SELECT CASE WHEN SUM(oi.price * oi.quantity) IS NULL THEN -1 ELSE SUM(oi.price * oi.quantity) END';
            } elseif ($Config->getType() == Config::ORDER_BY_QUANTITY) {
                $select = '(SELECT CASE WHEN SUM(oi.quantity) IS NULL THEN 1 ELSE SUM(oi.quantity + 1) END';
                // $select = '(SELECT CASE WHEN SUM(oi.quantity) IS NULL THEN -1 ELSE SUM(oi.quantity) END';
            }

            $select .= ' FROM \Eccube\Entity\OrderItem AS oi
                    LEFT JOIN oi.Order AS o
                    WHERE
                     oi.Product=p.id
                     AND o.OrderStatus not in (:excludes)';

            if ($Config->getTargetDays()) {
                $targetDate = new \DateTime();
                $targetDate->setTime(0, 0, 0, 0);
                $targetDate->sub(new \DateInterval('P'.$Config->getTargetDays().'D'));

                $qb->setParameter('target_date', $targetDate);
                $select .= ' AND o.order_date >= :target_date';
            }

            $select .= ') AS HIDDEN buy_quantity';

            $qb->addSelect($select)
                ->orderBy('buy_quantity', 'DESC')
                ->addOrderBy('p.approved_date', 'DESC')
                ->groupBy('p.id')
                ->setParameter('excludes', $excludes);
        }

        if ($originOptionNostockHidden) {
            if (!$this->entityManager->getFilters()->isEnabled('option_nostock_hidden')) {
                $this->entityManager->getFilters()->enable('option_nostock_hidden');
            }
        } else {
            if ($this->entityManager->getFilters()->isEnabled('option_nostock_hidden')) {
                $this->entityManager->getFilters()->disable('option_nostock_hidden');
            }
        }

        return $qb->getQuery()->getResult();
    }
    
    /**
     * @param integer $offset
     * @param integer $limit
     * 
     * @return array
     * 
     */
    public function getRanksByParameters( $offset = 0, $limit = 5)
    {
        $originOptionNostockHidden = $this->entityManager->getFilters()->isEnabled('option_nostock_hidden');

        // Doctrine SQLFilter
        if ($this->baseInfoRepository->get()->isOptionNostockHidden()) {
            $this->entityManager->getFilters()->enable('option_nostock_hidden');
        }

        $qb = $this->productRepository
            ->createQueryBuilder('p');
        $qb
            ->where('p.Status = :Disp')
            ->andWhere('p.launch_date <= :today')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->setParameter('today', new \DateTime())
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        /* @var $Config Config */
        $Config = $this->configRepository->findOneBy([]);

        // @see https://github.com/EC-CUBE/ec-cube/issues/1998
        if ($this->entityManager->getFilters()->isEnabled('option_nostock_hidden') == true) {
            $qb->innerJoin('p.ProductClasses', 'pc');
            $qb->andWhere('pc.visible = true');
        }

        $excludes = [OrderStatus::CANCEL, OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::RETURNED];

        if ($Config) {
            if ($Config->getType() == Config::ORDER_BY_AMOUNT) {
                $select = '(SELECT CASE WHEN SUM(oi.price * oi.quantity) IS NULL THEN 1 ELSE SUM(oi.price * oi.quantity + 1) END';
                // $select = '(SELECT CASE WHEN SUM(oi.price * oi.quantity) IS NULL THEN -1 ELSE SUM(oi.price * oi.quantity) END';
            } elseif ($Config->getType() == Config::ORDER_BY_QUANTITY) {
                $select = '(SELECT CASE WHEN SUM(oi.quantity) IS NULL THEN 1 ELSE SUM(oi.quantity + 1) END';
                // $select = '(SELECT CASE WHEN SUM(oi.quantity) IS NULL THEN -1 ELSE SUM(oi.quantity) END';
            }

            $select .= ' FROM \Eccube\Entity\OrderItem AS oi
                    LEFT JOIN oi.Order AS o
                    WHERE
                     oi.Product=p.id
                     AND o.OrderStatus not in (:excludes)';

            if ($Config->getTargetDays()) {
                $targetDate = new \DateTime();
                $targetDate->setTime(0, 0, 0, 0);
                $targetDate->sub(new \DateInterval('P'.$Config->getTargetDays().'D'));

                $qb->setParameter('target_date', $targetDate);
                $select .= ' AND o.order_date >= :target_date';
            }

            $select .= ') AS HIDDEN buy_quantity';

            $qb->addSelect($select)
                ->orderBy('buy_quantity', 'DESC')
                ->addOrderBy('p.approved_date', 'DESC')
                ->groupBy('p.id')
                ->setParameter('excludes', $excludes);
        }

        if ($originOptionNostockHidden) {
            if (!$this->entityManager->getFilters()->isEnabled('option_nostock_hidden')) {
                $this->entityManager->getFilters()->enable('option_nostock_hidden');
            }
        } else {
            if ($this->entityManager->getFilters()->isEnabled('option_nostock_hidden')) {
                $this->entityManager->getFilters()->disable('option_nostock_hidden');
            }
        }

        $CustomerReviewTotalRepository = $this->entityManager->getRepository(CustomerReviewTotal::class);
        $result = [];
        
        foreach ($qb->getQuery()->getResult() as $Product) {
            $Categories = [];
            foreach ( $Product->getProductCategories() as $ProductCategory ) {
                $Categories[] = $ProductCategory->getCategory()->getName();
            }

            $reviewList = $CustomerReviewTotalRepository->getRecommend($Product->getId());
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
}
