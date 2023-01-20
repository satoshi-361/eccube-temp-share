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

namespace Plugin\OrderBySale4;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\OrderBySale4\Entity\Config;
use Plugin\OrderBySale4\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigRepository $configRepository
    ) {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_PRODUCT_INDEX_SEARCH => 'onFrontProductIndexSearch',
        ];
    }

    public function onFrontProductIndexSearch(EventArgs $eventArgs)
    {
        /* @var $qb QueryBuilder */
        $qb = $eventArgs->getArgument('qb');
        $searchData = $eventArgs->getArgument('searchData');

        $ProductListOrderBy = $searchData['orderby'];

        if ($ProductListOrderBy) {
            $Config = $this->configRepository->findOneBy([
                'product_list_order_by_id' => $ProductListOrderBy->getId(),
            ]);

            $excludes = [OrderStatus::CANCEL, OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::RETURNED];

            if ($Config) {
                if ($Config->getType() == Config::ORDER_BY_AMOUNT) {
                    $qb->addSelect('(SELECT CASE WHEN SUM(oi.price * oi.quantity) IS NULL THEN -1 ELSE SUM(oi.price * oi.quantity) END
                    FROM \Eccube\Entity\OrderItem AS oi 
                    LEFT JOIN oi.Order AS o 
                    WHERE 
                     oi.Product=p.id 
                     AND o.OrderStatus not in (:excludes)
                     ) AS HIDDEN  buy_quantity')
                        ->orderBy('buy_quantity', 'DESC')
                        ->groupBy('p.id')
                        ->setParameter('excludes', $excludes)
                    ;
                } elseif ($Config->getType() == Config::ORDER_BY_QUANTITY) {
                    $qb->addSelect('(SELECT CASE WHEN SUM(oi.quantity) IS NULL THEN -1 ELSE SUM(oi.quantity) END
                    FROM \Eccube\Entity\OrderItem AS oi 
                    LEFT JOIN oi.Order AS o 
                    WHERE 
                     oi.Product=p.id 
                     AND o.OrderStatus not in (:excludes)
                     ) AS HIDDEN buy_quantity')
                        ->orderBy('buy_quantity', 'DESC')
                        ->groupBy('p.id')
                        ->setParameter('excludes', $excludes)
                    ;
                }
            }
        }
    }
}
