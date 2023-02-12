<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;

class UpdateGmoEpsilonOrderService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PurchaseFlow $shoppingPurchaseFlow,
        OrderStatusRepository $orderStatusRepository,
        OrderRepository $orderRepository
    ) {
        $this->entityManager = $entityManager;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderRepository = $orderRepository;
    }

    public function updateAfterMakingPayment(Order $Order, string $transCode, string $gmoEpsilonOrderNo)
    {
        $this->purchaseFlow->commit($Order, new PurchaseContext());

        // 受注ステータスを新規受付へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $Order
            ->setOrderDate(new \DateTime())
            ->setOrderStatus($OrderStatus)
            ->setPaymentDate(new \DateTime())
            ->setTransCode($transCode)
            ->setGmoEpsilonOrderNo($gmoEpsilonOrderNo)
        ;

        $this->entityManager->persist($Order);
        $this->entityManager->flush();
    }
}
