<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Order;

use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddCompletePaymentButtonEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/edit.twig' => 'edit',
        ];
    }

    public function edit(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');
        // 新規作成時は表示しない
        if (is_null($Order->getId())) {
            return;
        }
        // 登録済みのクレジットカード決済以外は表示しない
        if (!is_null($Order->getPayment()) && $Order->getPayment()->getMethodClass() !== Reg_Credit::class) {
            return;
        }
        // gmo_epsilon_order_numberが登録済みの場合は表示しない
        if (!empty($Order->getGmoEpsilonOrderNo())) {
            return;
        }
        $event->addSnippet('@EccubePaymentLite4/admin/Order/complete_payment_button.twig');
    }
}
