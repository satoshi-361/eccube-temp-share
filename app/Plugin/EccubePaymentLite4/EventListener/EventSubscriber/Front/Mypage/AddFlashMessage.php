<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddFlashMessage implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Mypage/delivery.twig' => 'delivery',
            'Mypage/change_complete.twig' => 'change_complete',
        ];
    }

    public function delivery(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@EccubePaymentLite4/default/Mypage/Alert/alert.twig');
    }

    public function change_complete(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@EccubePaymentLite4/default/Mypage/Alert/alert.twig');
    }
}
