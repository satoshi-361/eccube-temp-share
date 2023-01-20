<?php

namespace Plugin\PayPalCheckout\Controller\OrderManagement;

use Eccube\Controller\AbstractController as EccubeAbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Shipping;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Repository\OrderPdfRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\ProductStockRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Eccube\Service\OrderPdfService;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UpdateOrderStatusController
 * @package Plugin\PayPalCheckout\Controller\OrderManagement
 */
class UpdateOrderStatusController extends EccubeAbstractController
{
    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var SexRepository
     */
    protected $sexRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /** @var OrderPdfRepository */
    protected $orderPdfRepository;

    /**
     * @var ProductStockRepository
     */
    protected $productStockRepository;

    /** @var OrderPdfService */
    protected $orderPdfService;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * UpdateOrderStatusController constructor.
     * @param PurchaseFlow $orderPurchaseFlow
     * @param CsvExportService $csvExportService
     * @param CustomerRepository $customerRepository
     * @param PaymentRepository $paymentRepository
     * @param SexRepository $sexRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductStockRepository $productStockRepository
     * @param OrderRepository $orderRepository
     * @param OrderPdfRepository $orderPdfRepository
     * @param ValidatorInterface $validator
     * @param OrderStateMachine $orderStateMachine
     * @param MailService $mailService
     */
    public function __construct(
        PurchaseFlow $orderPurchaseFlow,
        CsvExportService $csvExportService,
        CustomerRepository $customerRepository,
        PaymentRepository $paymentRepository,
        SexRepository $sexRepository,
        OrderStatusRepository $orderStatusRepository,
        PageMaxRepository $pageMaxRepository,
        ProductStatusRepository $productStatusRepository,
        ProductStockRepository $productStockRepository,
        OrderRepository $orderRepository,
        OrderPdfRepository $orderPdfRepository,
        ValidatorInterface $validator,
        OrderStateMachine $orderStateMachine,
        MailService $mailService
    ) {
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->csvExportService = $csvExportService;
        $this->customerRepository = $customerRepository;
        $this->paymentRepository = $paymentRepository;
        $this->sexRepository = $sexRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productStockRepository = $productStockRepository;
        $this->orderRepository = $orderRepository;
        $this->orderPdfRepository = $orderPdfRepository;
        $this->validator = $validator;
        $this->orderStateMachine = $orderStateMachine;
        $this->mailService = $mailService;
    }

    /**
     * @param Request $request
     * @param Shipping $Shipping
     * @param OrderStatus $OrderStatus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function eccubeAdminOrderUpdateOrderStatus(Request $request, Shipping $Shipping, OrderStatus $OrderStatus)
    {
//        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
//            return $this->json(['status' => 'NG'], 400);
//        }

        $Order = $Shipping->getOrder();
//        $OrderStatus = $this->entityManager->find(OrderStatus::class, $request->get('order_status'));
//
//        if (!$OrderStatus) {
//            return $this->json(['status' => 'NG'], 400);
//        }

        $result = [];
        try {
            if ($Order->getOrderStatus()->getId() == $OrderStatus->getId()) {
                log_info('対応状況一括変更スキップ');
                $result = ['message' => trans('admin.order.skip_change_status', ['%name%' => $Shipping->getId()])];
            } else {
                if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                    if ($OrderStatus->getId() == OrderStatus::DELIVERED) {
                        if (!$Shipping->isShipped()) {
                            $Shipping->setShippingDate(new \DateTime());
                        }
                        $allShipped = true;
                        foreach ($Order->getShippings() as $Ship) {
                            if (!$Ship->isShipped()) {
                                $allShipped = false;
                                break;
                            }
                        }
                        if ($allShipped) {
                            $this->orderStateMachine->apply($Order, $OrderStatus);
                        }
                    } else {
                        $this->orderStateMachine->apply($Order, $OrderStatus);
                    }

                    if ($request->get('notificationMail')) { // for SimpleStatusUpdate
                        $this->mailService->sendShippingNotifyMail($Shipping);
                        $Shipping->setMailSendDate(new \DateTime());
                        $result['mail'] = true;
                    } else {
                        $result['mail'] = false;
                    }
                    // 対応中・キャンセルの更新時は商品在庫を増減させているので商品情報を更新
                    if ($OrderStatus->getId() == OrderStatus::IN_PROGRESS || $OrderStatus->getId() == OrderStatus::CANCEL) {
                        foreach ($Order->getOrderItems() as $OrderItem) {
                            $ProductClass = $OrderItem->getProductClass();
                            if ($OrderItem->isProduct() && !$ProductClass->isStockUnlimited()) {
                                $this->entityManager->flush($ProductClass);
                                $ProductStock = $this->productStockRepository->findOneBy(['ProductClass' => $ProductClass]);
                                $this->entityManager->flush($ProductStock);
                            }
                        }
                    }
                    $this->entityManager->flush($Order);
                    $this->entityManager->flush($Shipping);

                    // 会員の場合、購入回数、購入金額などを更新
                    if ($Customer = $Order->getCustomer()) {
                        $this->orderRepository->updateOrderSummary($Customer);
                        $this->entityManager->flush($Customer);
                    }
                } else {
                    $from = $Order->getOrderStatus()->getName();
                    $to = $OrderStatus->getName();
                    $result = ['message' => trans('admin.order.failed_to_change_status', [
                        '%name%' => $Shipping->getId(),
                        '%from%' => $from,
                        '%to%' => $to,
                    ])];
                }

                log_info('対応状況一括変更処理完了', [$Order->getId()]);
            }
        } catch (\Exception $e) {
            log_error('予期しないエラーです', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }

        return $this->json(array_merge(['status' => 'OK'], $result));
    }
}
