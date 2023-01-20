<?php

namespace Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout;

use Eccube\Entity\Order;
use Eccube\Service\OrderHelper;
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
 * Class OrderingProductController
 * @package Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout
 */
class OrderingProductController extends ShortcutPayPalCheckoutController
{
    /**
     * @Method("POST")
     * @Route("/paypal/shortcut-prepare-transaction/{cart_key}", name="paypal_shortcut_prepare_transaction", requirements={"cart_key" = "[a-zA-Z0-9]+[_][\x20-\x7E]+"})
     * @param Request $request
     * @param $cart_key
     * @return JsonResponse
     */
    public function __invoke(Request $request, $cart_key): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug('OrderingProduct has been received', [
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
            $this->paypal->createOrderRequest($order, function ($response) use (&$orderingId, &$statusCode) {
                $orderingId = $response->result->id;
                $statusCode = $response->statusCode;
                $this->paypal->saveShortcutPayPalCheckoutToken($orderingId);
            });

            /** @var array $data */
            $data = [
                "result" => [
                    "id" => $orderingId
                ]
            ];
            $this->logger->debug('OrderingProduct has been completed', [
                'response' => $data
            ]);
            return $this->json($data, $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('OrderingProduct has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->paypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
            throw new BadRequestHttpException("OrderingProduct has been failed", $e, $e->getCode());
        }
    }
}
