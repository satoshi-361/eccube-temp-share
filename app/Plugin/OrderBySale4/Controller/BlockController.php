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

namespace Plugin\OrderBySale4\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductRepository;
use Plugin\OrderBySale4\Entity\Config;
use Plugin\OrderBySale4\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BlockController extends AbstractController
{
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
        ProductRepository $productRepository,
        ConfigRepository $configRepository,
        BaseInfoRepository $baseInfoRepository
    ) {
        $this->productRepository = $productRepository;
        $this->configRepository = $configRepository;
        $this->baseInfoRepository = $baseInfoRepository;
    }

    /**
     * @Route("/block/order_by_sale", name="block_order_by_sale")
     *
     * @Template("Block/order_by_sale.twig")
     */
    public function index(Request $request)
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
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW);

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
                $select = '(SELECT CASE WHEN SUM(oi.price * oi.quantity) IS NULL THEN -1 ELSE SUM(oi.price * oi.quantity) END';
            } elseif ($Config->getType() == Config::ORDER_BY_QUANTITY) {
                $select = '(SELECT CASE WHEN SUM(oi.quantity) IS NULL THEN -1 ELSE SUM(oi.quantity) END';
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
                ->groupBy('p.id')
                ->setParameter('excludes', $excludes);
        }

        $qb->setMaxResults($Config->getBlockDisplayNumber());
        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = true);

        $Products = [];

        foreach ($paginator as $Product) {
            $Products[] = $Product;
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

        return [
            'Products' => $Products,
        ];
    }
}
