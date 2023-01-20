<?php

namespace Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout\Guest;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Service\OrderHelper;
use Exception;
use Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout\ShortcutPayPalCheckoutController;
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
 * @package Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout\Guest
 */
class OrderingProductController extends ShortcutPayPalCheckoutController
{
    /**
     * 注文内容を更新する
     * ショートカット決済で PayPal に遷移する前に呼ばれる
     *
     * @Method("POST")
     * @Route("/guest/paypal/shortcut-prepare-transaction/{cart_key}", name="guest_paypal_shortcut_prepare_transaction", requirements={"cart_key" = "[a-zA-Z0-9]+[_][\x20-\x7E]+"})
     * @param Request $request
     * @param $cart_key
     * @return JsonResponse
     */
    public function __invoke(Request $request, $cart_key): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        $this->logger->debug('CreateShortcutOrderRequest has been received', [
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
            $this->initializeCustomer($this->generateDummyUser());
        } catch (Exception $e) {
            return $this->json([], Response::HTTP_FORBIDDEN);
        }

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
            }, false);

            /** @var array $data */
            $data = [
                "result" => [
                    "id" => $orderingId
                ]
            ];
            $this->logger->debug('CreateShortcutOrderRequest has been completed', [
                'response' => $data
            ]);
            return $this->json($data, $statusCode);
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('CreateShortcutOrderRequest has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->paypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
            throw new BadRequestHttpException("CreateShortcutOrderRequest has been failed", $e, $e->getCode());
        } finally {
            $this->session->remove(OrderHelper::SESSION_NON_MEMBER);
            $this->session->remove(OrderHelper::SESSION_NON_MEMBER_ADDRESSES);
        }
    }

    /**
     * @return Customer
     */
    private function generateDummyUser(): Customer
    {
        $data = [
            'name01' => '',
            'name02' => '',
            'kana01' => '',
            'kana02' => '',
            'company_name' => null,
            'postal_code' => '',
            'pref' => $this->prefRepository->find(1),
            'addr01' => '',
            'addr02' => '',
            'phone_number' => '',
            'email' => ''
        ];

        /** @var Customer $dummyCustomer */
        $dummyCustomer = new Customer();
        $dummyCustomer
            ->setName01($data['name01'])
            ->setName02($data['name02'])
            ->setKana01($data['kana01'])
            ->setKana02($data['kana02'])
            ->setCompanyName($data['company_name'])
            ->setEmail($data['email'])
            ->setPhonenumber($data['phone_number'])
            ->setPostalcode($data['postal_code'])
            ->setPref($data['pref'])
            ->setAddr01($data['addr01'])
            ->setAddr02($data['addr02']);
        return $dummyCustomer;
    }
}
