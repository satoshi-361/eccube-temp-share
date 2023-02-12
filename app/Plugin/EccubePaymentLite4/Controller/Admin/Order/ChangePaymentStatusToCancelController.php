<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Order;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Shipping;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestCancelPaymentService;
use Plugin\EccubePaymentLite4\Service\UpdatePaymentStatusService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChangePaymentStatusToCancelController extends AbstractController
{
    /**
     * @var UpdatePaymentStatusService
     */
    private $updatePaymentStatusService;
    /**
     * @var RequestCancelPaymentService
     */
    private $requestCancelPaymentService;

    public function __construct(
        RequestCancelPaymentService $requestCancelPaymentService,
        UpdatePaymentStatusService $updatePaymentStatusService
    ) {
        $this->requestCancelPaymentService = $requestCancelPaymentService;
        $this->updatePaymentStatusService = $updatePaymentStatusService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/order/change_payment_status_to_cancel",
     *     name="eccube_payment_lite4_change_payment_status_to_cancel"
     * )
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        /** @var Shipping $Shipping */
        $Shipping = $request->query->get('Shipping');
        $Order = $Shipping->getOrder();
        $results = $this
            ->requestCancelPaymentService
            ->handle($Order);
        if ($results['status'] === 'OK') {
            // 決済ステータスを更新する
            $this->updatePaymentStatusService->handle($Order, PaymentStatus::CANCEL);
        }

        return $this->json([
            'status' => $results['status'],
            'message' => $results['message'],
        ]);
    }
}
