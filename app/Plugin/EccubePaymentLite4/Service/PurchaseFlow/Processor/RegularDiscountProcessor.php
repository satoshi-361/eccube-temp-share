<?php

namespace Plugin\EccubePaymentLite4\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Service\PurchaseFlow\DiscountProcessor;
use Eccube\Service\PurchaseFlow\ProcessResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\EccubePaymentLite4\Entity\RegularDiscount;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Service\IsRegularPaymentService;
use Plugin\EccubePaymentLite4\Service\RegularDiscountService;

/**
 * @ShoppingFlow
 */
class RegularDiscountProcessor implements DiscountProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IsRegularPaymentService
     */
    private $isRegularPaymentService;

    /**
     * @var RegularDiscountService
     */
    private $regularDiscountService;

    /**
     * constructor.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        IsRegularPaymentService $isRegularPaymentService,
        RegularDiscountService $regularDiscountService
    ) {
        $this->entityManager = $entityManager;
        $this->isRegularPaymentService = $isRegularPaymentService;
        $this->regularDiscountService = $regularDiscountService;
    }

    /**
     * 値引き明細の削除処理を実装します.
     */
    public function removeDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        foreach ($itemHolder->getItems() as $item) {
            if ($item->getProcessorName() == RegularDiscountProcessor::class) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    /**
     * 値引き明細の追加処理を実装します.
     *
     * かならず合計金額等のチェックを行い, 超える場合は利用できる金額まで丸めるか、もしくは明細の追加処理をスキップしてください.
     * 正常に追加できない場合は, ProcessResult::warnを返却してください.
     *
     * @return ProcessResult|null
     *
     * @throws NoResultException
     */
    public function addDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        /** @var Shipping $Shipping */
        $Shipping = $itemHolder->getShippings()->first();
        if (empty($Shipping) || $Shipping->getDelivery()->getSaleType()->getName() !== '定期商品') {
            return null;
        }

        if (!$this->isRegularPaymentService->isRegularPayment($itemHolder)) {
            return null;
        }

        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $itemHolder instanceof Order ? $itemHolder->getRegularOrder() : null;
        $regularCount = !empty($RegularOrder) ? $RegularOrder->getRegularOrderCount() : 0;
        $discountPrice = 0;

        /** @var OrderItem $item */
        foreach ($itemHolder->getItems() as $item) {
            $ProductClass = $item->getProductClass();
            /** @var RegularDiscount $RegularDiscount */
            $RegularDiscount = $ProductClass ? $ProductClass->getRegularDiscount() : null;

            if ($item->isProduct() && $RegularDiscount) {
                $discountRate = $this->regularDiscountService->getDiscountRate($RegularDiscount->getDiscountId(), $regularCount + 1);
                $discountPrice += !empty($discountRate) ? $this->regularDiscountService->getDiscountPrice($item, $discountRate) : 0;
            }
        }

        if ($discountPrice > 0) {
            $this->regularDiscountService->addDiscountItem($itemHolder, $discountPrice);
        }

        return null;
    }
}
