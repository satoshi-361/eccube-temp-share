<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddDeferredPaymentDescriptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');
        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['deferred']) {
            $event->addSnippet('@EccubePaymentLite4/default/Shopping/gmo_deferred_payment_description.twig');
        }
    }
}
