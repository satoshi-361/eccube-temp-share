<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Order;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Shipping;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestShippingRegistrationService;
use Plugin\EccubePaymentLite4\Service\UpdatePaymentStatusService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChangePaymentStatusToShippingRegistrationController extends AbstractController
{
    /**
     * @var RequestShippingRegistrationService
     */
    private $requestShippingRegistrationService;
    /**
     * @var UpdatePaymentStatusService
     */
    private $updatePaymentStatusService;

    public function __construct(
        RequestShippingRegistrationService $requestShippingRegistrationService,
        UpdatePaymentStatusService $updatePaymentStatusService
    ) {
        $this->requestShippingRegistrationService = $requestShippingRegistrationService;
        $this->updatePaymentStatusService = $updatePaymentStatusService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/order/change_from_temporary_sales_to_shipping_registration",
     *     name="eccube_payment_lite4_change_payment_status_to_shipping_registration",
     *     requirements={"id" = "\d+"}
     * )
     */
    public function index(Request $request)
    {
        /** @var Shipping $Shipping */
        $Shipping = $request->get('Shipping');
        $Order = $Shipping->getOrder();
        $results = $this->requestShippingRegistrationService->handle($Order);
        if ($results['status'] === 'OK') {
            // 決済ステータスを更新する
            $this->updatePaymentStatusService->handle($Order, PaymentStatus::SHIPPING_REGISTRATION);
        }

        return $this->json([
            'status' => $results['status'],
            'message' => $results['message'],
        ]);
    }
}
