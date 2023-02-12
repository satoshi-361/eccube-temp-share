<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Order;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ShippingRepository;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChangePaymentStatusController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var ShippingRepository
     */
    private $shippingRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ShippingRepository $shippingRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->shippingRepository = $shippingRepository;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/order/change_payment_status",
     *     name="eccube_payment_lite4_admin_change_payment_status",
     *     methods={"POST"}
     * )
     */
    public function index(Request $request)
    {
        $shippingId = (int) $request->request->get('shippingId');
        /** @var Shipping $Shipping */
        $Shipping = $this->shippingRepository->find($shippingId);
        $orderId = $Shipping->getOrder()->getId();
        // 変更対象の決済ステータスを取得
        $paymentStatusId = (int) $request->request->get('paymentStatusId');
        /** @var Order $Order */
        $Order = $this->orderRepository->find($orderId);
        if (is_null($Order)) {
            return $this->json([
                'status' => 'NG',
                'message' => 'ID: '.$Order->getId().'の受注がありませんでした。',
            ]);
        }

        if ($paymentStatusId === PaymentStatus::CANCEL) {
            return $this->forwardToRoute(
                'eccube_payment_lite4_change_payment_status_to_cancel',
                [],
                ['Shipping' => $Shipping]
            );
        }
        if ($paymentStatusId === PaymentStatus::CHARGED) {
            return $this->forwardToRoute(
                'eccube_payment_lite4_change_payment_status_to_charged',
                [],
                ['Shipping' => $Shipping]
            );
        }

        if ($paymentStatusId === PaymentStatus::SHIPPING_REGISTRATION) {
            return $this->forwardToRoute(
                'eccube_payment_lite4_change_payment_status_to_shipping_registration',
                [],
                ['Shipping' => $Shipping]
            );
        }

        return $this->json([
            'status' => 'NG',
            'message' => 'ID: '.$Order->getId().'の受注の決済ステータスは、一括変更できません。',
        ]);
    }
}
