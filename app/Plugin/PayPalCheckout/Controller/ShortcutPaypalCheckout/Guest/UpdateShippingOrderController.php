<?php

namespace Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout\Guest;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Exception;
use Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout\ShortcutPayPalCheckoutController;
use Plugin\PayPalCheckout\Exception\NotFoundCartException;
use Plugin\PayPalCheckout\Exception\NotFoundOrderingIdException;
use Plugin\PayPalCheckout\Exception\OrderInitializationException;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UpdateShippingOrderController
 * @package Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout\Guest
 */
class UpdateShippingOrderController extends ShortcutPayPalCheckoutController
{
    /**
     * お届け先情報を PayPal から送信された情報で上書きする場合に、「ご注文内容確認」ページの間に呼ばれる
     *
     * @Method("POST")
     * @Route("/guest/paypal/order/{cart_key}", name="guest_paypal_order", requirements={"cart_key" = "[a-zA-Z0-9]+[_][\x20-\x7E]+"})
     * @param Request $request
     * @param $cart_key
     * @return JsonResponse
     */
    public function __invoke(Request $request, $cart_key): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug('UpdateOrderRequest has been received', [
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
            /** @var Customer $customer */
            $customer = $this->paypal->getShippingCustomer();
            $this->initializeCustomer($customer);
            $this->updateOrder($this->cartPurchaseFlow, $customer);
        } catch (OrderInitializationException $e) {
            return $this->json([], Response::HTTP_FORBIDDEN);
        } catch (NotFoundOrderingIdException $e) {
            return $this->json([], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return $this->json([], Response::HTTP_FORBIDDEN);
        }

        $this->logger->debug('Shipping order has been initialized.', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        try {
            /** @var Order $order */
            $order = $this->paypal->getShippingOrder();
            $this->paypal->updateOrderRequest($order, function ($response) use (&$orderingId, &$statusCode) {
                $statusCode = $response->statusCode;
            });

            /** @var array $data */
            $data = [
                "result" => []
            ];
            $this->logger->debug('UpdateOrderRequest has been completed', [
                'response' => $data
            ]);
            return $this->json($data, $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('UpdateOrderRequest has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->paypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
            throw new BadRequestHttpException("UpdateOrderRequest has been failed", $e, $e->getCode());
        }
    }

    /**
     * @param PurchaseFlow $cartPurchaseFlow
     * @param Customer $customer
     * @return Order
     * @throws OrderInitializationException
     */
    private function updateOrder(PurchaseFlow $cartPurchaseFlow, Customer $customer): void
    {
        try {
            parent::updateGuestOrder($cartPurchaseFlow, $customer);
        } catch (Exception $e) {
            throw new OrderInitializationException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
