<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Service\Method\Conveni;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddConveniFormEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function index(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');
        if (!is_null($Order->getPayment()) && Conveni::class === $Order->getPayment()->getMethodClass()) {
            $event->addSnippet('@EccubePaymentLite4/default/Shopping/conveni_form.twig');
        }
    }

    public function confirm(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');
        if (!is_null($Order->getPayment()) && Conveni::class === $Order->getPayment()->getMethodClass()) {
            $event->addSnippet('@EccubePaymentLite4/default/Shopping/conveni_confirm_form.twig');
        }
    }
}
