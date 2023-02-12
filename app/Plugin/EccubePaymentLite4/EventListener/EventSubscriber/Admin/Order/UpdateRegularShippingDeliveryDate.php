<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Order;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateRegularShippingDeliveryDate implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var object|null
     */
    private $Config;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigRepository $configRepository
    ) {
        $this->entityManager = $entityManager;
        $this->Config = $configRepository->get();
    }

    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE => 'adminOrderEditIndexComplete',
        ];
    }

    public function adminOrderEditIndexComplete(EventArgs $eventArgs)
    {
        // そもそも定期が設定されていない場合、定期要処理を行わない.
        if (!$this->Config->getRegular()) {
            return;
        }
        /** @var Order $Order */
        $Order = $eventArgs->getArgument('TargetOrder');
        // 定期受注ではない場合は処理を行わなくインスタンスがプラグインのものでない場合は処理を行わいない
        if (!$Order->getRegularOrder() instanceof RegularOrder || is_null($Order->getRegularOrder())) {
            return;
        }
        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();
        /** @var RegularShipping $RegularShipping */
        $RegularShipping = $Order->getRegularOrder()->getRegularShippings()->first();
        $RegularShipping->setShippingDeliveryDate($Shipping->getShippingDeliveryDate());
        $this->entityManager->persist($RegularShipping);
        $this->entityManager->flush();
    }
}
