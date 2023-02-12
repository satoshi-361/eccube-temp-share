<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AddFlashBug implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        SessionInterface $session
    ) {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_COMPLETE => 'frontMypageChangeIndexComplete',
            EccubeEvents::FRONT_MYPAGE_DELIVERY_EDIT_COMPLETE => 'frontMypageDeliveryEditComplete',
        ];
    }

    public function frontMypageDeliveryEditComplete(EventArgs $eventArgs)
    {
        $this->session->getFlashBag()->add('eccube.front.warning', '定期商品のお届け先は変更されませんのでご注意ください。');
    }

    public function frontMypageChangeIndexComplete(EventArgs $eventArgs)
    {
        $this->session->getFlashBag()->add('eccube.front.warning', '定期商品のお届け先は変更されませんのでご注意ください。');
    }
}
