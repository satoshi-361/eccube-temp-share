<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Order;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GetProductInformationFromOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonOrderNoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\GetCardCgiUrlService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetSales2Service;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetUserInfoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Symfony\Component\Routing\Annotation\Route;

class CreateRegCreditOrderController extends AbstractController
{
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var GetProductInformationFromOrderService
     */
    private $getProductInformationFromOrderService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var GmoEpsilonOrderNoService
     */
    private $gmoEpsilonOrderNoService;
    /**
     * @var RequestGetUserInfoService
     */
    private $requestGetUserInfoService;
    /**
     * @var GetCardCgiUrlService
     */
    private $getCardCgiUrlService;
    /**
     * @var RequestReceiveOrderService
     */
    private $requestReceiveOrderService;
    /**
     * @var RequestGetSales2Service
     */
    private $requestGetSales2Service;

    public function __construct(
        OrderRepository $orderRepository,
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        GetProductInformationFromOrderService $getProductInformationFromOrderService,
        ConfigRepository $configRepository,
        GmoEpsilonOrderNoService $gmoEpsilonOrderNoService,
        RequestGetUserInfoService $requestGetUserInfoService,
        GetCardCgiUrlService $getCardCgiUrlService,
        RequestReceiveOrderService $requestReceiveOrderService,
        RequestGetSales2Service $requestGetSales2Service
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->orderRepository = $orderRepository;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->getProductInformationFromOrderService = $getProductInformationFromOrderService;
        $this->configRepository = $configRepository;
        $this->gmoEpsilonOrderNoService = $gmoEpsilonOrderNoService;
        $this->requestGetUserInfoService = $requestGetUserInfoService;
        $this->getCardCgiUrlService = $getCardCgiUrlService;
        $this->requestReceiveOrderService = $requestReceiveOrderService;
        $this->requestGetSales2Service = $requestGetSales2Service;
    }

    /**
     * @Route(
     *     "%eccube_admin_route%/eccube_payment_lite/order/{id}/create_reg_credit_order",
     *     name="eccube_payment_lite4_admin_create_reg_credit_order"
     * )
     */
    public function index($id)
    {
        /** @var Order $Order */
        $Order = $this->orderRepository->find($id);
        $Customer = $Order->getCustomer();
        if (is_null($Customer)) {
            $this->addError(
                '非会員の受注は、イプシロン決済登録が出来ません。',
                'admin'
            );

            return $this->redirectToRoute('admin_order_edit', [
                'id' => $Order->getId(),
            ]);
        }
        if (!is_null($Order->getPayment())
            && $Order->getPayment()->getMethodClass() !== Reg_Credit::class) {
            $this->addError(
                $this->eccubeConfig['gmo_epsilon']['pay_name']['reg_credit'].'以外の受注は、イプシロン決済登録が出来ません。',
                'admin'
            );

            return $this->redirectToRoute('admin_order_edit', [
                'id' => $Order->getId(),
            ]);
        }

        $getSalesResult = $this->requestGetSales2Service->handle(null, $Order->getGmoEpsilonOrderNo());
        // gmo_epsilon_order_noが未登録または、gmo_epsilon_order_noに紐づく決済情報が無い場合
        if (is_null($Order->getGmoEpsilonOrderNo()) || $getSalesResult['order_number'] !== 0) {
            $results = $this
                ->requestReceiveOrderService
                ->handle($Customer, 2, 'eccube_payment_lite4_admin_create_reg_credit_order', $Order);
            if ($results['status'] === 'NG') {
                $this->addError(
                    $results['message'],
                    'admin'
                );

                return $this->redirectToRoute('admin_order_edit', [
                    'id' => $Order->getId(),
                ]);
            }

            return $this->redirect($results['url']);
        }

        // 決済登録済みの場合
        $this
            ->addWarning('すでにイプシロン決済サービスに決済情報を登録済みです。', 'admin');

        return $this->redirectToRoute('admin_order_edit', [
            'id' => $Order->getId(),
        ]);
    }
}
