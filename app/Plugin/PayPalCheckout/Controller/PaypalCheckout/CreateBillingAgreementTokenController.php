<?php

namespace Plugin\PayPalCheckout\Controller\PaypalCheckout;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Service\LoggerService;
use Plugin\PayPalCheckout\Service\PayPalService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class CreateBillingAgreementTokenController
 * @package Plugin\PayPalCheckout\Controller
 */
class CreateBillingAgreementTokenController extends AbstractController
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
     * CreateBillingAgreementTokenController constructor.
     * @param PayPalService $paypal
     * @param LoggerService $loggerService
     */
    public function __construct(
        PayPalService $paypal,
        LoggerService $loggerService
    ) {
        $this->paypal = $paypal;
        $this->logger = $loggerService;
    }

    /**
     * @Method("POST")
     * @Route("/paypal/prepare-subscription", name="paypal_prepare_subscription")
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug('CreateBillingAgreementToken has been received', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            /** @var Order $order */
            $order = $this->paypal->getShippingOrder();
            $this->paypal->createBillingAgreementTokenRequest($order, function ($response) use (&$tokenId, &$statusCode) {
                $tokenId = $response->result->token_id;
                $statusCode = $statusCode = $response->statusCode;
            });

            /** @var array $data */
            $data = [
                "result" => [
                    "id" => $tokenId
                ]
            ];
            $this->logger->debug('CreateBillingAgreementToken has been completed', [
                'response' => $data
            ]);
            return $this->json($data, $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('CreateBillingAgreementToken has been failed', [
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            throw new BadRequestHttpException("CreateBillingAgreementToken has been failed", $e, $e->getCode());
        }
    }
}
