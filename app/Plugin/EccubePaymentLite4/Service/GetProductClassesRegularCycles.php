<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Plugin\EccubePaymentLite4\Entity\ProductClassRegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;

class GetProductClassesRegularCycles
{
    /**
     * @var ProductClassRepository
     */
    private $productClassRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ProductClassRepository $productClassRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->productClassRepository = $productClassRepository;
        $this->entityManager = $entityManager;
    }

    public function handle(Order $Order)
    {
        $productClassIds = $this->getProductClassIds($Order);
        $regularCycleIds = $this->getRegularCycleIds($Order);
        $qb = $this
            ->entityManager
            ->createQueryBuilder();
        $qb
            ->select('rc')
            ->from(ProductClass::class, 'pc')
            ->innerJoin(ProductClassRegularCycle::class, 'pcrc')
            ->innerJoin(RegularCycle::class, 'rc')
            ->where(
                $qb->expr()->in('pc.id', ':productClassIds')
            )
            ->andWhere(
                $qb->expr()->in('rc.id', ':regularCycleIds')
            )
            ->setParameter('productClassIds', $productClassIds)
            ->setParameter('regularCycleIds', $regularCycleIds)
            ->orderBy('rc.sort_no', 'DESC')
        ;

        return $qb
            ->getQuery()
            ->getResult();
    }

    private function getProductClassIds(Order $Order)
    {
        $productClassIds = [];
        foreach ($Order->getProductOrderItems() as $key => $OrderItem) {
            $productClassId = $OrderItem->getProductClass()->getId();
            $productClassIds[$productClassId] = $productClassId;
        }

        return $productClassIds;
    }

    private function getRegularCycleIds(Order $Order)
    {
        $regularCycleIds = [];
        foreach ($Order->getProductOrderItems() as $key => $OrderItem) {
            $tempRegularCycleIds = [];
            foreach ($OrderItem->getProductClass()->getRegularCycle() as $regularCycle) {
                /** @var RegularCycle $regularCycle */
                $regularCycleId = $regularCycle->getId();
                $tempRegularCycleIds[] = $regularCycleId;
            }
            if ($key === 0) {
                $regularCycleIds = $tempRegularCycleIds;
                continue;
            }
            $regularCycleIds = array_intersect($regularCycleIds, $tempRegularCycleIds);
        }

        return $regularCycleIds;
    }
}
