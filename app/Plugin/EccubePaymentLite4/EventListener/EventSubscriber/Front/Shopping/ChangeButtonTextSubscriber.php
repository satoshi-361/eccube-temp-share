<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Deferred;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChangeButtonTextSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function confirm(TemplateEvent $event)
    {
        $PaymentMethod = $event->getParameter('Order')->getPayment()->getMethodClass();
        $event->setParameter('buttonText', '注文する');
        if ($PaymentMethod === Credit::class ||
            $PaymentMethod === Deferred::class) {
            $event->setParameter('buttonText', '次へ');
        }
    }
}
