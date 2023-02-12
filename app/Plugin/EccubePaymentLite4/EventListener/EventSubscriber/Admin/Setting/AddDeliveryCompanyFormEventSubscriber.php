<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Setting;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddDeliveryCompanyFormEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Setting/Shop/delivery_edit.twig' => 'deliveryEdit',
        ];
    }

    public function deliveryEdit(TemplateEvent $event)
    {
        $event->addSnippet('@EccubePaymentLite4/admin/Setting/delivery_company_form.twig');
    }
}
