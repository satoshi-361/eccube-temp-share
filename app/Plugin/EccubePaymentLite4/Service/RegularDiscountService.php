<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\OrderItem;
use Eccube\Repository\TaxRuleRepository;
use Plugin\EccubePaymentLite4\Entity\RegularDiscount;
use Plugin\EccubePaymentLite4\Repository\RegularDiscountRepository;
use Plugin\EccubePaymentLite4\Service\PurchaseFlow\Processor\RegularDiscountProcessor;
use Plugin\EccubePaymentLite4\Service\Util\CommonUtil;

class RegularDiscountService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RegularDiscountRepository
     */
    private $regularDiscountRepository;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RegularDiscountRepository $regularDiscountRepository,
        TaxRuleRepository $taxRuleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->regularDiscountRepository = $regularDiscountRepository;
        $this->taxRuleRepository = $taxRuleRepository;
    }

    /**
     * @param $discountRate
     *
     * @return float|int
     *
     * @throws NoResultException
     */
    public function getDiscountPrice(OrderItem $OrderItem, $discountRate)
    {
        $ProductClass = $OrderItem->getProductClass();
        $TaxRule = $this->taxRuleRepository->getByRule();
        $price = CommonUtil::roundByCalcRule(
            $ProductClass->getPrice02IncTax() * $discountRate / 100,
                $TaxRule->getRoundingType()->getId()
        ) * $OrderItem->getQuantity();

        return $price;
    }

    /**
     * Add Discount Item
     *
     * @param ItemHolderInterface $itemHolder
     * @param $price
     */
    public function addDiscountItem($itemHolder, $price)
    {
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::DISCOUNT);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $Taxation = $this->entityManager->find(TaxType::class, TaxType::NON_TAXABLE);

        $OrderItem = new OrderItem();
        $OrderItem->setProductName('定期回数別商品金額割引')
            ->setPrice($price * -1)
            ->setQuantity(1)
            ->setTax(0)
            ->setTaxRate(0)
            ->setRoundingType(null)
            ->setOrderItemType($DiscountType)
            ->setTaxDisplayType($TaxInclude)
            ->setTaxType($Taxation)
            ->setOrder($itemHolder)
            ->setProcessorName(RegularDiscountProcessor::class);

        $itemHolder->addItem($OrderItem);
    }

    public function getDiscountRate($discountId, $regularCount)
    {
        $maxNumberOfRegularCount = $this->regularDiscountRepository->getMaxNumberOfRegularCount($discountId, $regularCount);
        /** @var RegularDiscount $RegularDiscount */
        $RegularDiscount = $this->regularDiscountRepository->findOneBy([
            'discount_id' => $discountId,
            'regular_count' => $maxNumberOfRegularCount,
        ]);

        return $RegularDiscount ? $RegularDiscount->getDiscountRate() : null;
    }
}
