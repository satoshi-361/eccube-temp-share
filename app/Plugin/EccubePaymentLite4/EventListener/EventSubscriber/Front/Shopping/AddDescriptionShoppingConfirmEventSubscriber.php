<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Master\SaleType;
use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\EccubePaymentLite4\Service\RegularDiscountService;


class AddDescriptionShoppingConfirmEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var RegularDiscountService
     */
    private $regularDiscountService;

    public function __construct(
        ConfigRepository $configRepository,
        ContainerInterface $container,
        RegularDiscountService $regularDiscountService
    ) {
        $this->configRepository = $configRepository;
        $this->container = $container;
        $this->regularDiscountService = $regularDiscountService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function confirm(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');

        $quantity = 0;
        $ProductOrderItems = $Order->getProductOrderItems();
        foreach ($ProductOrderItems as $OrderItems){
            $quantity += $OrderItems->getQuantity();
        }

        /** @var SaleType $SaleType */
        $SaleType = $Order->getShippings()->first()->getDelivery()->getSaleType();

        $discountPrice = [];
        for ($regularCount = 1; $regularCount <= 5; $regularCount++) {
            $discountPrice[$regularCount] = 0;
            foreach ($Order->getItems() as $item) {
                $ProductClass = $item->getProductClass();
                /** @var RegularDiscount $RegularDiscount */
                $RegularDiscount = $ProductClass ? $ProductClass->getRegularDiscount() : null;
                if ($item->isProduct() && $RegularDiscount) {
                    $discountRate = $this->regularDiscountService->getDiscountRate($RegularDiscount->getDiscountId(), $regularCount);
                    $discountPrice[$regularCount] += !empty($discountRate) ? $this->regularDiscountService->getDiscountPrice($item, $discountRate) : 0;
                }
            }
        }
        if ($SaleType->getName() === '定期商品') {
            $event->setParameter('total_quantity_item', $quantity);
            $event->setParameter('ProductOrderItems', $ProductOrderItems);
            $event->setParameter('first_discount', $discountPrice[1]);
            $event->setParameter('second_discount', $discountPrice[2]);
            $event->setParameter('third_discount', $discountPrice[3]);
            $event->setParameter('forth_discount', $discountPrice[4]);
            $event->setParameter('fifth_discount', $discountPrice[5]);
            $event->addSnippet('@EccubePaymentLite4/default/Shopping/specified_commercial_transactions.twig');
        }

        $event->addSnippet('@EccubePaymentLite4/default/Shopping/specified_commercial_payment_time.twig');
    }
}
