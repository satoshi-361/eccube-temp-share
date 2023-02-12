<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChangeShoppingConfirmActionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ConfigRepository $configRepository,
        ContainerInterface $container
    ) {
        $this->configRepository = $configRepository;
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function confirm(TemplateEvent $event)
    {
        $PaymentMethod = $event->getParameter('Order')->getPayment()->getMethodClass();
        if (!($PaymentMethod === Credit::class)) {
            return;
        }
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $creditPaymentSetting = $Config->getCreditPaymentSetting();
        if ($creditPaymentSetting === Config::LINK_PAYMENT) {
            return;
        }
        // トークン決済かつクレジットカード決済の場合に以下の処理を行う
        $url = $this->container->get('router')->generate('eccube_payment_lite4_credit_card_for_token_payment');
        $event->setParameter('creditCardPaymentUrl', $url);
        $event->addSnippet('@EccubePaymentLite4/default/Shopping/change_shopping_confirm_action.twig');
    }
}
