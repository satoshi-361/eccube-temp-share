<?php

namespace Plugin\PayPalCheckout\Controller\Admin\PaymentStatus;

use Eccube\Entity\Master\OrderStatus;
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
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\PayPalCheckout\Controller\OrderManagement\UpdateOrderStatusController as UpdateInventoryController;
use Plugin\PayPalCheckout\Entity\Transaction;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Service\LoggerService;
use Plugin\PayPalCheckout\Service\PayPalService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RefoundController
 * @package Plugin\PayPalCheckout\Controller\Admin\PaymentStatus
 */
class RefoundController extends UpdateInventoryController
{
    /**
     * @var PayPalService
     */
    protected $paypal;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * RefoundController constructor.
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
     * @param PayPalService $paypal
     * @param LoggerService $loggerService
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
        MailService $mailService,
        PayPalService $paypal,
        LoggerService $loggerService
    ) {
        parent::__construct(
            $orderPurchaseFlow,
            $csvExportService,
            $customerRepository,
            $paymentRepository,
            $sexRepository,
            $orderStatusRepository,
            $pageMaxRepository,
            $productStatusRepository,
            $productStockRepository,
            $orderRepository,
            $orderPdfRepository,
            $validator,
            $orderStateMachine,
            $mailService
        );
        $this->paypal = $paypal;
        $this->logger = $loggerService;
    }

    /**
     * @Method("POST")
     * @Route("/%eccube_admin_route%/paypal/transaction/{id}/refound", requirements={"id" = "\d+"}, name="admin_paypal_transaction_refound")
     * @param Request $request
     * @param Transaction $transaction
     * @return JsonResponse
     */
    public function index(Request $request, Transaction $transaction): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug('RefoundRequest has been received', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            $params = new stdClass();
            $params->request = $request;
            $params->shipping = $transaction->getOrder()->getShippings()->first();
            $params->orderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
            $this->eccubeAdminOrderUpdateOrderStatus($params->request, $params->shipping, $params->orderStatus);

            $this->paypal->refound($transaction, function ($response) use (&$tokenId, &$statusCode, $params) {
                $statusCode = $statusCode = $response->statusCode;
//                注文取消しメール
            });
            $this->logger->debug('RefoundRequest has been completed', [
                'response' => []
            ]);
            return $this->json([], $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('RefoundRequest has been failed', [
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            throw new BadRequestHttpException("RefoundRequest has been failed", $e, $e->getCode());
        }
    }
}
