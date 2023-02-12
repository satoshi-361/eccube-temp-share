<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\PaymentNotification;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonOrderNoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConveniAndPayeasyNotificationController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var GmoEpsilonOrderNoService
     */
    private $gmoEpsilonOrderNoService;

    public function __construct(
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        GmoEpsilonOrderNoService $gmoEpsilonOrderNoService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;

        $this->gmoEpsilonOrderNoService = $gmoEpsilonOrderNoService;
    }

    /**
     * @Route(
     *     "/epsilon_receive_conveni_and_payeasy_complete",
     *     name="eccube_payment_lite4_conveni_and_payeasy_notification"
     * )
     */
    public function receiveConveniAndPayeasyComplete(Request $request): Response
    {
        logs('gmo_epsilon')->addInfo('コンビニ・ペイジー決済入金結果通知: start.');
        logs('gmo_epsilon')->addInfo('コンビニ・ペイジー決済入金結果通知: '.print_r($request->getContent(), true));
        // 受注情報を取得
        $Order = $this->orderRepository->findOneBy([
            'order_no' => $this->gmoEpsilonOrderNoService->get($request->get('order_number')),
            'trans_code' => $request->get('trans_code'),
        ]);

        if (!$Order) {
            logs('gmo_epsilon')->addWarning('コンビニ・ペイジー決済入金結果通知: Not Found Order. POST param argument '.print_r($request->getContent(), true));

            // 異常応答
            return new Response(0);
        }

        if ((int) $request->get('paid') === 1) {
            // 受注ステータスを入金済みへ変更
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
            $Order->setOrderStatus($OrderStatus);
            $Order->setPaymentDate(new \DateTime());

            $this->entityManager->flush();
            logs('gmo_epsilon')->addInfo('コンビニ・ペイジー決済入金結果通知: 受注ステータスを入金済みに変更しました');
        }

        logs('gmo_epsilon')->addInfo('コンビニ・ペイジー決済入金結果通知: end.');

        // 正常応答
        return new Response(1);
    }
}
