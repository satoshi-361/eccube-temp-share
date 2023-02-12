<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HideAddDeliveryBtnEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $templateEvent)
    {
        /** @var Order $Order */
        $Order = $templateEvent->getParameter('Order');
        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();
        /** @var SaleType $SaleType */
        $SaleType = $Shipping->getDelivery()->getSaleType();
        if ($SaleType->getName() === '定期商品') {
            $templateEvent->addSnippet('@EccubePaymentLite4/default/Shopping/hide_add_delivery_btn.twig');
        }
    }
}
