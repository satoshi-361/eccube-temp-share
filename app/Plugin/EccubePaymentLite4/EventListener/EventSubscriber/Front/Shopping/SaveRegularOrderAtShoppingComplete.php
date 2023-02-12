<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\EccubePaymentLite4\Service\IsRegularPaymentService;
use Plugin\EccubePaymentLite4\Service\SaveRegularOrderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SaveRegularOrderAtShoppingComplete implements EventSubscriberInterface
{
    /**
     * @var SaveRegularOrderService
     */
    private $saveRegularOrderService;
    /**
     * @var IsRegularPaymentService
     */
    private $isRegularPaymentService;

    public function __construct(
        SaveRegularOrderService $saveRegularOrderService,
        IsRegularPaymentService $isRegularPaymentService
    ) {
        $this->saveRegularOrderService = $saveRegularOrderService;
        $this->isRegularPaymentService = $isRegularPaymentService;
    }

    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => 'index',
        ];
    }

    public function index(EventArgs $eventArgs)
    {
        /** @var Order $Order */
        $Order = $eventArgs->getArgument('Order');
        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();
        if ($Shipping->getDelivery()->getSaleType()->getName() !== '定期商品') {
            return;
        }
        if (!$this->isRegularPaymentService->isRegularPayment($Order)) {
            return;
        }
        $this->saveRegularOrderService->save($Order);
    }
}
