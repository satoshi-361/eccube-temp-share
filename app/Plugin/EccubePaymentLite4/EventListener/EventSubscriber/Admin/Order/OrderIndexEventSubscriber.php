<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Order;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderRepository;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderIndexEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    public function __construct(
        OrderRepository $orderRepository,
        PaymentStatusRepository $paymentStatusRepository,
        EccubeConfig $eccubeConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->eccubeConfig = $eccubeConfig;
    }

    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/index.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $event)
    {
        $orderList = $event->getParameter('pagination')->getItems();
        $orders = [];
        foreach ($orderList as $key => $Order) {
            /* @var Order $Order */
            $orders[$key] = $Order->toArray();
            $orders[$key]['PaymentStatus'] = is_null($orders[$key]['PaymentStatus']) ? null : $orders[$key]['PaymentStatus']->toArray();
            $orders[$key]['Payment']['payment_method'] = is_null($orders[$key]['payment_method']) ? null : $orders[$key]['payment_method'];
            $orders[$key]['Shippings'] = is_null($orders[$key]['Shippings']) ? null : $orders[$key]['Shippings']->toArray();
            foreach ($Order->getShippings() as $index => $Shipping) {
                /* @var Shipping $Shipping */
                $orders[$key]['Shippings'][$index] = $Shipping->toArray();
            }
        }
        $displayOrders = [];
        foreach ($orders as $key => $order) {
            $displayOrders[$key]['PaymentStatus'] = $order['PaymentStatus'];
            $displayOrders[$key]['payment_method'] = $order['payment_method'];
            foreach ($order['Shippings'] as $index => $shipping) {
                $displayOrders[$key]['Shippings'][$index] = ['id' => $shipping['id']];
            }
        }
        $PaymentStatuses = $this->paymentStatusRepository->findBy([
            'id' => [
                PaymentStatus::CHARGED,
                PaymentStatus::SHIPPING_REGISTRATION,
                PaymentStatus::CANCEL,
            ],
        ], []);
        $event->setParameter('PaymentStatuses', $PaymentStatuses);
        $event->setParameter('orders', $displayOrders);
        $event->setParameter('virtual_account', $this->eccubeConfig['gmo_epsilon']['pay_name']['virtual_account']);
        $event->setParameter('payment_status_id', PaymentStatus::CHARGED);
        $event->addSnippet('@EccubePaymentLite4/admin/Order/index.twig');
    }
}
