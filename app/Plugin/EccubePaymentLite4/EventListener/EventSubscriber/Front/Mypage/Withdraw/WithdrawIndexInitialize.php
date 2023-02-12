<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage\Withdraw;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class WithdrawIndexInitialize implements EventSubscriberInterface
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
            EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_INITIALIZE => 'index',
        ];
    }

    public function index(EventArgs $eventArgs)
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
            // 解約以外のステータスの定期受注がある場合は、Requestのmodeの値を空にすることで、
            // Controllerでの解約の処理が実行されないようにしている。
            $eventArgs->getRequest()->request->set('mode', '');

            return;
        }
    }
}
