<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Eccube\Entity\Order;
use Eccube\Repository\TaxRuleRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;

class UpdateRegularOrderService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var CalculateNextDeliveryDateService
     */
    private $calculateNextDeliveryDateService;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;

    public function __construct(
        TaxRuleRepository $taxRuleRepository,
        ConfigRepository $configRepository,
        EntityManagerInterface $entityManager,
        CalculateNextDeliveryDateService $calculateNextDeliveryDateService,
        RegularStatusRepository $regularStatusRepository
    ) {
        $this->taxRuleRepository = $taxRuleRepository;
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
        $this->calculateNextDeliveryDateService = $calculateNextDeliveryDateService;
        $this->regularStatusRepository = $regularStatusRepository;
    }

    public function update(RegularOrder $RegularOrder, Order $Order)
    {
        /** @var RegularStatus $RegularStatus */
        $RegularStatus = $this->regularStatusRepository->find(RegularStatus::CONTINUE);
        $RegularOrder
            ->setRegularStatus($RegularStatus)
            ->setSubtotal($Order->getSubtotal())
            ->setTotal($Order->getTotal())
            ->setPaymentTotal($Order->getPaymentTotal())
        ;
        /** @var RegularOrderItem[] $RegularOrderItems */
        $RegularOrderItems = $RegularOrder->getRegularOrderItems();
        foreach ($RegularOrderItems as $RegularOrderItem) {
            if (!$RegularOrderItem->isProduct()) {
                continue;
            }
            $ProductClass = $RegularOrderItem->getProductClass();
            try {
                $taxRule = $this->taxRuleRepository->getByRule($ProductClass->getProduct(), $ProductClass);
            } catch (NoResultException $e) {
            }
            $taxRate = $taxRule->getTaxRate();
            $RegularOrderItem
                ->setProductName($ProductClass->getProduct()->getName())
                ->setProductCode($ProductClass->getCode())
                ->setPrice($ProductClass->getPrice02())
                ->setTaxRate($taxRate)
            ;
        }
        $this->entityManager->persist($RegularOrder);
        $this->entityManager->flush();
    }

    public function updateAfterMakingPayment(RegularOrder $RegularOrder)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        // 定期購入回数の更新
        $RegularOrder
            ->setRegularOrderCount($RegularOrder->getRegularOrderCount() + 1)
            ->setRegularSkipFlag(0);
        // 次回お届け予定日と締め日、お届け予定日の更新
        foreach ($RegularOrder->getRegularShippings() as $RegularShipping) {
            $nextDeliveryDate = $this
                ->calculateNextDeliveryDateService
                ->calc($RegularOrder->getRegularCycle(), $Config->getRegularOrderDeadline());
            $shippingDeliveryDate = new \DateTime('today');
            $shippingDeliveryDate->modify('+'.$Config->getRegularOrderDeadline().' day');
            /* @var RegularShipping $RegularShipping */
            $RegularShipping
                ->setShippingDeliveryDate($shippingDeliveryDate)
                ->setRegularOrder($RegularOrder)
                ->setNextDeliveryDate($nextDeliveryDate)
            ;
        }

        $this->entityManager->persist($RegularOrder);
        $this->entityManager->flush();
    }
}
