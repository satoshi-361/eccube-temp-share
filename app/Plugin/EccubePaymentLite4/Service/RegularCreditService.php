<?php

namespace Plugin\EccubePaymentLite4\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;

class RegularCreditService
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
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
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var OrderNoProcessor
     */
    private $orderNoProcessor;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RegularStatusRepository $regularStatusRepository,
        RegularOrderRepository $regularOrderRepository,
        OrderStatusRepository $orderStatusRepository,
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig,
        PurchaseFlow $shoppingPurchaseFlow,
        OrderNoProcessor $orderNoProcessor
    ) {
        $this->entityManager = $entityManager;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->regularStatusRepository = $regularStatusRepository;
        $this->regularOrderRepository = $regularOrderRepository;
        $this->configRepository = $configRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->orderNoProcessor = $orderNoProcessor;
    }

    public function createOrder(RegularOrder $RegularOrder)
    {
        $RegularStatus = $RegularOrder->getRegularStatus();

        // remove use point
        $RegularOrder->setUsePoint(0);
        // end remove use point

        if ($RegularStatus instanceof RegularStatus == false || $RegularStatus->getId() == $RegularStatus::CANCELLATION) {
            return null;
        }

        /** @var Order $Order */
        $Order = $this->createOrderData($RegularOrder);
        $Order->setRegularOrder($RegularOrder);
        $Order->setOrderDate(new DateTime());
        $flowResult = $this->purchaseFlow->validate($Order, new PurchaseContext(clone $Order, $Order->getCustomer()));
        if ($flowResult->hasError() == false) {
            $this->entityManager->persist($Order);
            $this->entityManager->flush();
            $this->orderNoProcessor->process($Order, new PurchaseContext(clone $Order, $Order->getCustomer()));
            $this->entityManager->flush();
        }

        return $Order;
    }

    public function createOrderData($RegularOrder)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $Order = new Order();
        $Order->copyProperties($RegularOrder, [
            'id',
            'pre_order_id',
            'order_no',
            'regular_order_count',
            'regular_status_id',
            'create_date',
            'update_date',
        ]);
        $Order
            ->setOrderStatus($this->orderStatusRepository->find(OrderStatus::PENDING))
            ->setCreateDate(new DateTime())
            ->setUpdateDate(new DateTime());

        // Shippings
        $RegularShippings = $RegularOrder->getRegularShippings();
        $shippingList = [];
        foreach ($RegularShippings as $RegularShipping) {
            $Shipping = new Shipping();
            $Shipping->copyProperties($RegularShipping, [
                'id',
                'delivery_date',
            ]);
            $shippingDeliveryDate = new \DateTime('today');
            $shippingDeliveryDate->modify('+'.$Config->getRegularOrderDeadline().' day');
            $Shipping
                ->setOrder($Order)
                ->setShippingDeliveryDate($shippingDeliveryDate)
            ;
            $Order->addShipping($Shipping);

            $shippingList[$RegularShipping->getId()] = $Shipping;
        }

        // OrderItems
        $RegularOrderItems = $RegularOrder->getRegularOrderItems();
        foreach ($RegularOrderItems as $RegularOrderItem) {
            $OrderItem = new OrderItem();
            $OrderItem->copyProperties($RegularOrderItem, ['id']);

            $OrderItem->setOrder($Order);
            $Order->addOrderItem($OrderItem);

            $RegularShipping = $RegularOrderItem->getRegularShipping();
            if ($RegularShipping) {
                $Shipping = $shippingList[$RegularShipping->getId()];
                $OrderItem->setShipping($Shipping);
                $Shipping->addOrderItem($OrderItem);
            }
        }

        return $Order;
    }
}
