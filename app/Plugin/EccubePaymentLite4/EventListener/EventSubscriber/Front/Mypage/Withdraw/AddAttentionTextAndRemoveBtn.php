<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage\Withdraw;

use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AddAttentionTextAndRemoveBtn implements EventSubscriberInterface
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        RegularOrderRepository $regularOrderRepository,
        SessionInterface $session
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Mypage/withdraw.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $templateEvent)
    {
        $Customer = unserialize($this->session->get('_security_customer'))->getUser();
        /** @var RegularOrder[] $RegularOrders */
        $RegularOrders = $this->regularOrderRepository->findBy([
            'Customer' => $Customer,
        ]);
        foreach ($RegularOrders as $RegularOrder) {
            if ($RegularOrder->getRegularStatus()->getId() === RegularStatus::CANCELLATION) {
                continue;
            }
            $templateEvent->addSnippet('@EccubePaymentLite4/default/Mypage/Withdraw/attention_text_and_remove_btn.twig');

            return;
        }
    }
}
