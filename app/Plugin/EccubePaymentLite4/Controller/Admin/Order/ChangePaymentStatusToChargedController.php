<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Order;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Shipping;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestSalesPaymentService;
use Plugin\EccubePaymentLite4\Service\UpdatePaymentStatusService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChangePaymentStatusToChargedController extends AbstractController
{
    /**
     * @var UpdatePaymentStatusService
     */
    private $updatePaymentService;
    /**
     * @var RequestSalesPaymentService
     */
    private $requestSalesPaymentService;

    public function __construct(
        UpdatePaymentStatusService $updatePaymentStatusService,
        RequestSalesPaymentService $requestSalesPaymentService
    ) {
        $this->updatePaymentService = $updatePaymentStatusService;
        $this->requestSalesPaymentService = $requestSalesPaymentService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/order/change_payment_status_to_charged",
     *     name="eccube_payment_lite4_change_payment_status_to_charged",
     *     requirements={"id" = "\d+"}
     * )
     */
    public function index(Request $request)
    {
        /** @var Shipping $Shipping */
        $Shipping = $request->get('Shipping');
        $Order = $Shipping->getOrder();
        $results = $this->requestSalesPaymentService->handle($Order);
        if ($results['status'] === 'OK') {
            $this->updatePaymentService->handle($Order, PaymentStatus::CHARGED);
        }

        return $this->json([
            'status' => $results['status'],
            'message' => $results['message'],
        ]);
    }
}
