<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\PaymentNotification;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeferredPaymentStatusChangeNotificationController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    public function __construct(
        OrderRepository $orderRepository,
        PaymentStatusRepository $paymentStatusRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
    }

    /**
     * @Route(
     *     "/epsilon_deferred_payment_status_change_notification",
     *     name="eccube_payment_lite4_deferred_payment_status_change_notification"
     * )
     */
    public function index(Request $request)
    {
        logs('gmo_epsilon')->addInfo('後払い決済ステータス変更通知: start.');
        logs('gmo_epsilon')->addInfo('POST param argument '.print_r($request->getContent(), true));
        /** @var Order $Order */
        $Order = $this->orderRepository->findOneBy([
            'gmo_epsilon_order_no' => $request->get('order_number'),
            'trans_code' => $request->get('trans_code'),
            'payment_method' => 'GMO後払い',
        ]);
        if (!$Order) {
            logs('gmo_epsilon')
                ->addWarning('後払い決済ステータス変更通知: 対象の受注が見つかりません。');

            return new Response(0);
        }

        /** @var PaymentStatus $PaymentStatus */
        $PaymentStatus = $this
            ->paymentStatusRepository
            ->find($request->get('state'));
        $Order->setPaymentStatus($PaymentStatus);
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        logs('gmo_epsilon')
            ->addInfo('後払い決済ステータス変更通知: end.');

        return new Response(1);
    }
}
