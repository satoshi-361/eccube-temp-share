<?php

namespace Plugin\PayPalCheckout\Controller\Acdc;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Service\LoggerService;
use Plugin\PayPalCheckout\Service\PayPalAcdcService;
use Plugin\PayPalCheckout\Service\PayPalService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class CreateAcdcOrderController
 * @package Plugin\PayPalCheckout\Controller\InlineGuest
 */
class CreateAcdcOrderController extends AbstractController
{
    /**
     * @var PayPalService
     */
    protected $paypal;

    /**
     * @var PayPalAcdcService
     */
    protected $payPalAcdcService;

    /**
     * @var ConfigRepository
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * CreateAcdcOrderController constructor.
     * @param PayPalService $paypal
     * @param PayPalAcdcService $payPalAcdcService
     * @param ConfigRepository $configRepository
     * @param LoggerService $loggerService
     */
    public function __construct(
        PayPalService $paypal,
        PayPalAcdcService $payPalAcdcService,
        ConfigRepository $configRepository,
        LoggerService $loggerService
    ) {
        $this->paypal = $paypal;
        $this->payPalAcdcService = $payPalAcdcService;
        $this->config = $configRepository->get();
        $this->logger = $loggerService;
    }

    /**
     * @Method("POST")
     * @Route("/paypal/prepare-transaction-acdc", name="paypal_prepare_transaction_acdc")
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->isTokenValid()) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug('CreateOrderRequest has been received', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            /** @var Order $order */
            $order = $this->paypal->getShippingOrder();
            // vault の情報が body に渡ってくるので取得
            if ($this->payPalAcdcService->canUseVault()) {
                $body = json_decode($request->getContent());
                $vaultId = $body->vaultId ?? '';
                $saveVault = $body->saveVault ?? false;
            } else {
                $vaultId = '';
                $saveVault = false;
            }

            $this->paypal->createOrderRequest($order, function ($response) use (&$orderingId, &$statusCode) {
                $orderingId = $response->result->id;
                $statusCode = $response->statusCode;
            }, true, $vaultId);
            // vault にカード情報を保存する場合は決済後に処理が発生するので状態を保存しておく
            $this->payPalAcdcService->setSaveVaultToSession($saveVault);

            /** @var array $data */
            $data = [
                "id" => $orderingId
            ];
            $this->logger->debug('CreateOrderRequest has been completed', $data);
            return $this->json($data, $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('CreateOrderRequest has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->paypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
            throw new BadRequestHttpException("CreateOrderRequest has been failed", $e, $e->getCode());
        }
    }
}
