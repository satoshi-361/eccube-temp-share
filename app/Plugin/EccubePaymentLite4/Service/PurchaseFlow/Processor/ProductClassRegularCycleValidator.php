<?php

namespace Plugin\EccubePaymentLite4\Service\PurchaseFlow\Processor;

use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Request\Context;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\EccubePaymentLite4\Service\GetProductClassesRegularCycles;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @ShoppingFlow
 */
class ProductClassRegularCycleValidator extends ItemHolderValidator
{
    /**
     * @var GetProductClassesRegularCycles
     */
    private $getProductClassesRegularCycles;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        GetProductClassesRegularCycles $getProductClassesRegularCycles,
        Context $context,
        SessionInterface $session
    ) {
        $this->getProductClassesRegularCycles = $getProductClassesRegularCycles;
        $this->context = $context;
        $this->session = $session;
    }

    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if ($this->context->isAdmin()) {
            return;
        }
        /** @var Order $Order */
        $Order = $itemHolder;
        if ($Order->getShippings()->first()) {
            if ($Order->getShippings()->first()->getDelivery()->getSaleType()->getName() !== '定期商品') {
                return;
            }
            $regularCycleChoices = $this->getProductClassesRegularCycles->handle($Order);
            if (empty($regularCycleChoices)) {
                $this->throwInvalidItemException('定期サイクルが異なる商品は、同時購入が出来ません。', null, false);
            }
        }
    }
}
