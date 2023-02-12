<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Service\PurchaseFlow\Processor\PointProcessor;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularCycleRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SaveRegularOrderService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var RegularCycleRepository
     */
    private $regularCycleRepository;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var CalculateNextDeliveryDateService
     */
    private $calculateNextDeliveryDateService;
    /**
     * @var SaleTypeRepository
     */
    private $saleTypeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RegularStatusRepository $regularStatusRepository,
        ConfigRepository $configRepository,
        RegularCycleRepository $regularCycleRepository,
        SaleTypeRepository $saleTypeRepository,
        SessionInterface $session,
        CalculateNextDeliveryDateService $calculateNextDeliveryDateService
    ) {
        $this->entityManager = $entityManager;
        $this->regularStatusRepository = $regularStatusRepository;
        $this->configRepository = $configRepository;
        $this->saleTypeRepository = $saleTypeRepository;
        $this->regularCycleRepository = $regularCycleRepository;
        $this->session = $session;
        $this->calculateNextDeliveryDateService = $calculateNextDeliveryDateService;
    }

    public function setRegularCycleIdInSession($regularCycleId)
    {
        $this->session->set(
            'regular_cycle_id',
            $regularCycleId
        );
    }

    public function save(Order $Order)
    {
        // 一度しか処理が行われないように、セッションを削除している。
        if (is_null($this->session->get('regular_cycle_id'))) {
            return;
        }
        $regularCycleId = $this->session->get('regular_cycle_id');
        $this->session->remove('regular_cycle_id');

        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $saleTypeId = $Order->getShippings()[0]->getDelivery()->getSaleType()->getId();
        /** @var SaleType $SaleType */
        $SaleType = $this->saleTypeRepository->find($saleTypeId);
        if ($SaleType->getName() !== '定期商品') {
            return;
        }
        // 配送予定日が入力されていない場合、受注日+初回配送予定日の日数で登録
        foreach ($Order->getShippings() as $Shipping) {
            if (is_null($Shipping->getShippingDeliveryDate())) {
                $num = $Config->getFirstDeliveryDays();
                $Shipping->setShippingDeliveryDate(new \DateTime('+'.$num.' day'));
            }
        }

        $RegularOrder = new RegularOrder();
        /** @var RegularStatus $RegularStatus */
        $RegularStatus = $this->
            regularStatusRepository
            ->find(RegularStatus::CONTINUE);

        // RegularOrder
        $RegularOrder->copyProperties($Order, [
            'id',
            'create_date',
            'update_date',
        ]);

        /** @var RegularCycle $RegularCycle */
        $RegularCycle = $this
            ->regularCycleRepository
            ->find($regularCycleId);

        $RegularOrder
            ->setTransCode($Order->getTransCode())
            ->setRegularOrderCount(1)
            ->setRegularSkipFlag(0)
            ->setRegularStatus($RegularStatus)
            ->setRegularCycle($RegularCycle);

        // RegularShipping
        $Shippings = $Order->getShippings();
        $shippingList = [];
        foreach ($Shippings as $Shipping) {
            $RegularShipping = new RegularShipping();
            $RegularShipping->copyProperties($Shipping, ['id']);
            $nextDeliveryDate = $this->calculateNextDeliveryDateService
                ->calc($RegularOrder->getRegularCycle(), $this->getDiffShippingDeliveryDate($Shipping->getShippingDeliveryDate()));
            // お届け予定日が入力されている場合は、現在日付との差分の日付を取得する。
            $RegularShipping
                ->setRegularOrder($RegularOrder)
                ->setNextDeliveryDate($nextDeliveryDate)
            ;
            $RegularOrder->addRegularShipping($RegularShipping);

            $shippingList[$Shipping->getId()] = $RegularShipping;
        }

        // RegularOrderItem
        $OrderItems = $Order->getOrderItems();
        foreach ($OrderItems as $OrderItem) {
            if ($OrderItem->getProcessorName() == PointProcessor::class) {
                continue;
            }

            $RegularOrderItem = new RegularOrderItem();
            $RegularOrderItem->copyProperties($OrderItem, ['id']);

            $RegularOrderItem
                ->setRegularOrder($RegularOrder);
            $RegularOrder->addRegularOrderItem($RegularOrderItem);

            $Shipping = $OrderItem->getShipping();
            if ($Shipping) {
                $RegularShipping = $shippingList[$Shipping->getId()];
                $RegularOrderItem
                    ->setRegularShipping($RegularShipping);
                $RegularShipping->addRegularOrderItem($RegularOrderItem);
            }
        }
        // remove use point
        $this->removePointRegularOrder($Order, $RegularOrder);
        // end remove use point

        $this->entityManager->persist($RegularOrder);
        $this->entityManager->flush();

        // 受注テーブルに定期受注IDを登録
        $Order->setRegularOrder($RegularOrder);
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
    }

    private function getDiffShippingDeliveryDate(\DateTime $shippingDeliveryDate): int
    {
        $today = new \DateTime('today');
        $interval = $today->diff($shippingDeliveryDate);

        return (int) $interval->format('%a');
    }

    /**
     * Remove point in RegularOrder
     */
    public function removePointRegularOrder(Order $Order, RegularOrder $RegularOrder)
    {
        /** @var OrderItem $OrderItem */
        $OrderItems = $Order->getItems();
        $discountPrices = 0;
        foreach ($OrderItems as $OrderItemKey => $OrderItem) {
            if ($OrderItem->isPoint()) {
                $discountPrices = $discountPrices - $OrderItem->getPrice();
            }
        }

        $RegularOrder->setDiscount($RegularOrder->getDiscount() - $discountPrices)
                     ->setTotal($RegularOrder->getTotal() + $discountPrices)
                     ->setPaymentTotal($RegularOrder->getPaymentTotal() + $discountPrices)
                     ->setUsePoint(0);
    }
}
