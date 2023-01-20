<?php

namespace Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout;

use Eccube\Entity\Order;
use Plugin\PayPalCheckout\Exception\NotFoundCartException;
use Plugin\PayPalCheckout\Exception\OrderInitializationException;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class OrderingSubscriptionProductController
 * @package Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout
 */
class OrderingSubscriptionProductController extends ShortcutPayPalCheckoutController
{
    /**
     * @Method("POST")
     * @Route("/paypal/shortcut/ordering-subscription-product/{cart_key}", name="paypal_shortcut_ordering_subscription_product", requirements={"cart_key" = "[a-zA-Z0-9]+[_][\x20-\x7E]+"})
     * @param Request $request
     * @param $cart_key
     * @return JsonResponse
     */
    public function __invoke(Request $request, $cart_key): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug("OrderingSubscriptionProduct has been received", [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            $this->lockCart($cart_key);
        } catch (NotFoundCartException $e) {
            return $this->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->logger->debug('Shopping cart has been locked.', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            $this->initializeOrder($this->cartPurchaseFlow);
        } catch (OrderInitializationException $e) {
            return $this->json([], Response::HTTP_FORBIDDEN);
        }

        $this->logger->debug('Shipping order has been initialized.', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            /** @var Order $order */
            $order = $this->paypal->getShippingOrder();
            $this->paypal->createBillingAgreementTokenRequest($order, function ($response) use (&$tokenId, &$statusCode) {
                $tokenId = $response->result->token_id;
                $statusCode = $statusCode = $response->statusCode;
                $this->paypal->saveShortcutPayPalCheckoutToken($tokenId);
            });

            /** @var array $data */
            $data = [
                "result" => [
                    "id" => $tokenId
                ]
            ];
            $this->logger->debug('OrderingSubscriptionProduct has been completed', [
                'response' => $data
            ]);
            return $this->json($data, $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('OrderingSubscriptionProduct has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->paypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
            throw new BadRequestHttpException("OrderingSubscriptionProduct has been failed", $e, $e->getCode());
        }
    }
}
