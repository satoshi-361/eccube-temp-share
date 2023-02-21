<?php

namespace Plugin\PayPalCheckout\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Exception;
use Plugin\PayPalCheckout\Contracts\CaptureTransactionResponse;
use Plugin\PayPalCheckout\Contracts\EccubeAddressAccessible;
use Plugin\PayPalCheckout\Contracts\OrderResultResponse;
use Plugin\PayPalCheckout\Contracts\ShowOrderDetailsResponse;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Entity\SubscribingCustomer;
use Plugin\PayPalCheckout\Entity\Transaction;
use Plugin\PayPalCheckout\Exception\NotFoundBillingTokenException;
use Plugin\PayPalCheckout\Exception\NotFoundOrderingIdException;
use Plugin\PayPalCheckout\Exception\NotFoundPurchaseProcessingOrderException;
use Plugin\PayPalCheckout\Exception\PayPalCaptureException;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Exception\PayPalRequestException;
use Plugin\PayPalCheckout\Exception\ReFoundPaymentException;
use Plugin\PayPalCheckout\Exception\ReFoundSubscriptionPaymentException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Repository\SubscribingCustomerRepository;
use Plugin\PayPalCheckout\Repository\TransactionRepository;
use Plugin\PayPalCheckout\Service\Method\BankTransfer;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PayPalService
 * @package Plugin\PayPalCheckout\Service
 */
class PayPalService
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var PayPalOrderService
     */
    private $order;

    /**
     * @var PayPalRequestService
     */
    private $client;

    /**
     * @var PayPalAcdcService
     */
    private $payPalAcdcService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var SubscribingCustomerRepository
     */
    private $subscribingCustomerRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * PayPalService constructor.
     * @param EccubeConfig $eccubeConfig
     * @param PayPalOrderService $orderService
     * @param PayPalRequestService $requestService
     * @param PayPalAcdcService $payPalAcdcService
     * @param LoggerService $loggerService
     * @param TransactionRepository $transactionRepository
     * @param SubscribingCustomerRepository $subscribingCustomerRepository
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        PayPalOrderService $orderService,
        PayPalRequestService $requestService,
        PayPalAcdcService $payPalAcdcService,
        LoggerService $loggerService,
        TransactionRepository $transactionRepository,
        SubscribingCustomerRepository $subscribingCustomerRepository,
        ConfigRepository $configRepository
    ) {
        $this->config = $configRepository->get();
        $this->eccubeConfig = $eccubeConfig;
        $this->order = $orderService;
        $this->client = $requestService;
        $clientId = $this->config->getClientId();
        $clientSecret = $this->config->getClientSecret();
        $this->client->setEnv($clientId, $clientSecret, $this->config->getUseSandbox());
        $this->payPalAcdcService = $payPalAcdcService;
        $this->logger = $loggerService;
        $this->transactionRepository = $transactionRepository;
        $this->subscribingCustomerRepository = $subscribingCustomerRepository;
    }

    /**
     * @param Cart $cart
     * @return bool
     */
    public static function existsSubscriptionProductInCart(Cart $cart): bool
    {
        /** @var CartItem $cartItem */
        foreach ($cart->getCartItems() as $cartItem) {
            if ($cartItem->getProductClass()->getUseSubscription()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public static function existsSubscriptionProductInOrderItems(Order $order): bool
    {
        /** @var OrderItem[] $items */
        $items = $order->getProductOrderItems();

        /** @var OrderItem $item */
        foreach ($items as $item) {
            if ($item->getProductClass()->getUseSubscription()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Cart $cart
     * @return string
     */
    public static function getCartAmount(Cart $cart): string
    {
        $amount = $cart->getTotalPrice();
        return $amount;
    }

    /**
     * @return bool
     */
    public function useExpressBtn(): bool
    {
        return $this->config->getUseExpressBtn() === true;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->eccubeConfig->get('paypal.debug') ?? false;
    }

    /**
     * @param string $id
     */
    public function saveShortcutPayPalCheckoutToken(string $id): void
    {
        $this->order->setShortcutPaymentSession($id);
    }

    /**
     * @return Order
     * @throws NotFoundPurchaseProcessingOrderException
     */
    public function getShippingOrder(): Order
    {
        return $this->order->getPurchaseProcessingOrder();
    }

    /**
     * @return Customer
     * @throws NotFoundOrderingIdException
     * @throws Exception
     */
    public function getShippingCustomer(): Customer
    {
        /** @var OrderResultResponse|EccubeAddressAccessible|null $response */
        $response = null;
        $this->order->doProcessOrderingId(function ($orderingId) use (&$response) {
            $transaction = $this->client->prepareOrderDetailTransaction($orderingId);
            $response = $this->client->orderDetailTransaction($transaction);
        });
        $this->order->setOrderingId($response->getOrderingId());

        /** @var Customer $customer */
        $customer = $this->order->generateCustomer($response);
        return $customer;
    }

    /**
     * @param Order $order
     * @param callable $afterProcessing
     * @param bool $authenticatedUser
     * @param string $vaultId
     * @throws PayPalRequestException
     * @throws PayPalCheckoutException
     */
    public function createOrderRequest(Order $order, callable $afterProcessing, $authenticatedUser = true, $vaultId = ''): void
    {
        /** @var stdClass $options */
        $options = new stdClass();
        $this->setApplicationContext($options, $order, $authenticatedUser);
        $this->setItemAndTotalPrice($options, $order);
        $this->setCustomer($options, $order);
        $this->setShipping($options, $order, $authenticatedUser);

        $transaction = $this->client->prepareOrderTransaction($options, $order->getPayment()->getMethodClass());

        $this->logger->debug("CreateOrderRequest contains the following parameters", $transaction->body);

        /** @var OrderResultResponse $response */
        $response = $this->client->orderTransaction($transaction);
        $this->order->setOrderingId($response->getOrderingId());

        // vault ID はここでは利用しないが、決済時に利用するのでセッションに保存しておく
        if (!empty($vaultId)) {
            $this->payPalAcdcService->setVaultIdToSession($vaultId);
        }

        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Order $order
     * @param callable $afterProcessing
     * @throws NotFoundOrderingIdException
     * @throws PayPalCheckoutException
     */
    public function updateOrderRequest(Order $order, callable $afterProcessing): void
    {
        /** @var OrderResultResponse|null $response */
        $response = null;

        /** @var stdClass $options */
        $options = new stdClass();
        $this->setItemAndTotalPrice($options, $order);
        unset($options->items);

        $this->order->doProcessOrderingId(function ($orderingId) use (&$response, $options) {
            $transaction = $this->client->prepareOrderPatchTransaction($orderingId, $options);
            $response  = $this->client->orderPatchTransaction($transaction);
        });
        $this->order->setOrderingId($response->getOrderingId());
        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Order $order
     * @param callable $afterProcessing
     * @throws PayPalRequestException
     * @throws PayPalCheckoutException
     */
    public function createBillingAgreementTokenRequest(Order $order, callable $afterProcessing): void
    {
        /** @var Shipping $shipping */
        $shipping = $order->getShippings()->first();

        /** @var stdClass $options */
        $options = new stdClass();
        $this->setShippingAddressSubscription($options, $shipping);

        $transaction = $this->client->prepareBillingAgreementToken($options);
        $this->logger->debug("CreateBillingAgreementToken contains the following parameters", $transaction->body);
        $response = $this->client->billingAgreementToken($transaction);
        $this->order->setBillingToken($response->result->token_id);
        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Order $order
     * @param callable $afterProcessing
     * @throws NotFoundOrderingIdException
     * @throws ReFoundPaymentException
     * @throws PayPalCaptureException
     */
    private function makeOneTimePayment(Order $order, callable $afterProcessing)
    {
        $response = null;
        $vaultId = $this->payPalAcdcService->extractVaultIdFromSession();
        $fraudNetSessionId = $this->payPalAcdcService->extractFraudNetSessionIdentifierFromSession();
        $this->order->doProcessOrderingId(function ($orderingId) use (&$response, $vaultId, $fraudNetSessionId) {
            $transaction = $this->client->prepareCaptureTransaction($orderingId, $vaultId, $fraudNetSessionId);
            $this->logger->debug("CaptureTransaction contains the following parameters", $transaction->body ?? []);
            /** @var CaptureTransactionResponse $response */
            $response = $this->client->captureTransaction($transaction);
        });

        /**
         * 何らかの理由で、トランザクションが失敗した場合、EC-CUBEの決済処理を無効にするため、例外を送出する。
         */
        if (!isset($response) || $response->isNg()) {
            throw new PayPalCaptureException($response->getDebugId());
        }

        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new ReFoundPaymentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 3Dセキュア認証が有効な場合、認証成功したかを確認する
     *
     * @throws NotFoundOrderingIdException
     */
    public function verify3dsecure(): bool
    {
        $orderingId = $this->order->getOrderingId();
        $request = $this->client->prepareShowOrderDetails($orderingId);
        $this->logger->debug("ShowOrderDetails contains the following parameters", $request->body ?? []);
        /** @var ShowOrderDetailsResponse $response */
        $response = $this->client->showOrderDetails($request);
        return $response->isOk();
    }

    /**
     * @param Order $order
     * @throws NotFoundBillingTokenException
     * @throws NotFoundOrderingIdException
     * @throws PayPalRequestException
     * @throws ReFoundPaymentException
     * @throws ReFoundSubscriptionPaymentException
     */
    public function checkout(Order $order): void
    {
        if (self::existsSubscriptionProductInOrderItems($order)) {
            $this->subscriptionPayment($order);
        } else {
            $this->payment($order);
        }
    }

    /**
     * @param Order $Order
     * @param SubscribingCustomer $subscribingCustomer
     * @param callable $afterProcessing
     * @throws PayPalRequestException
     * @throws ReFoundSubscriptionPaymentException
     */
    public function subscription(Order $Order, SubscribingCustomer $subscribingCustomer, callable $afterProcessing): void
    {
        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();

        /** @var stdClass $options */
        $options = new stdClass();
        $options->invoice_number = $Order->getId();
        $this->setItemAndTotalPriceSubscription($options, $Order);
        $this->setShippingAddressSubscription($options, $Shipping);

        /** @var string $billingAgreementId */
        $billingAgreementId = $subscribingCustomer->getReferenceTransaction()->getBillingAgreementId();
        $transaction = $this->client->prepareReferenceTransaction($billingAgreementId, $options);
        $this->logger->debug("CreateBillingAgreementPayment contains the following parameters", $transaction->body);
        $response = $this->client->referenceTransactionPayment($transaction);

        try {
            /** @var Transaction $transaction */
            $transaction = $this->transactionRepository->saveSuccessfulTransaction($Order, $response);
            call_user_func($afterProcessing, $response, $transaction);
        } catch (Exception $e) {
            throw new ReFoundSubscriptionPaymentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Transaction $transaction
     * @param callable $afterProcessing
     * @throws PayPalRequestException
     * @throws PayPalCheckoutException
     */
    public function refound(Transaction $transaction, callable $afterProcessing): void
    {
        $transaction = $this->client->prepareRefoundTransaction($transaction->getCaptureId());
        $response = $this->client->refoundTransaction($transaction);

        try {
            $transaction = $this->transactionRepository->saveSuccessfulTransaction($this->order, $response);
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            if ($e->getCode() === Response::HTTP_UNPROCESSABLE_ENTITY) {
                throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
            } else {
                throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @param Order $order
     * @param callable $afterProcessing
     * @throws NotFoundBillingTokenException
     * @throws PayPalRequestException
     * @throws ReFoundSubscriptionPaymentException
     */
    private function makeFirstSubscriptionPayment(Order $order, callable $afterProcessing): void
    {
        /** @var string|null $billingAgreementId */
        $billingAgreementId = null;
        $this->order->doProcessBillingToken(function ($billingToken) use (&$billingAgreementId) {
            $transaction = $this->client->prepareBillingAgreement($billingToken);
            $response = $this->client->billingAgreement($transaction);
            $billingAgreementId = $response->result->id;
        });

        /** @var Shipping $Shipping */
        $Shipping = $order->getShippings()->first();

        /** @var stdClass $options */
        $options = new stdClass();
        $options->invoice_number = $order->getPreOrderId();
        $this->setItemAndTotalPriceSubscription($options, $order);
        $this->setShippingAddressSubscription($options, $Shipping);

        $transaction = $this->client->prepareReferenceTransaction($billingAgreementId, $options);
        $this->logger->debug("CreateBillingAgreementPayment contains the following parameters", $transaction->body);
        $response = $this->client->referenceTransactionPayment($transaction);
        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new ReFoundSubscriptionPaymentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Order $order
     * @throws NotFoundOrderingIdException
     * @throws ReFoundPaymentException
     */
    private function payment(Order $order): void
    {
        try {
            $this->makeOneTimePayment($order, function ($response) use ($order) {
                $this->transactionRepository->saveSuccessfulTransaction($order, $response);
            });
        } catch (ReFoundPaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param Order $order
     * @throws NotFoundBillingTokenException
     * @throws PayPalRequestException
     * @throws ReFoundSubscriptionPaymentException
     */
    private function subscriptionPayment(Order $order): void
    {
        try {
            $this->makeFirstSubscriptionPayment($order, function ($response) use ($order) {
                /** @var Transaction $referenceTransaction */
                $referenceTransaction = $this->transactionRepository->saveSuccessfulTransaction($order, $response);
                $this->subscribingCustomerRepository->agreement($referenceTransaction);
            });
        } catch (ReFoundSubscriptionPaymentException $e) {
            throw $e;
        }
    }

    /**
     * 商品、価格情報を付与する
     * PayPal管理ツールの税表示がわかりにくいため、現在は税込価格をセットするようにしている。
     *
     * @param stdClass $options
     * @param Order $order
     */
    private function setItemAndTotalPrice(stdClass &$options, Order $order): void
    {
        /** @var array $products */
        $products = array_map(function (OrderItem $item): array {
            return [
                'name' => $item->getProductName(),
                'description' => $item->getProductName(),
                'sku' => $item->getProductCode(),
                'unit_amount' => [
                    'currency_code' => $item->getCurrencyCode(),
                    // 税込価格を送る
                    'value' => $item->getPriceIncTax(),
                    //'value' => $this->client->roundedCurrencyFormat($item->getPrice()),
                ],
                //'tax' => [
                //    'currency_code' => $item->getCurrencyCode(),
                //    'value' => $this->client->roundedCurrencyFormat($item->getTax())
                //],
                'quantity' => $item->getQuantity(),
            ];
        }, $order->getProductOrderItems());

        $options->amount = [
            'currency_code' => 'JPY',
            'value' => $this->client->roundedCurrencyFormat($order->getTotal()),
            'breakdown' => [
                'item_total' => [
                    'currency_code' => 'JPY',
                    'value' => $this->client->roundedCurrencyFormat(array_reduce($products, function ($carry, array $product): int {
                        // unit_amountだが現在は税込価格が入っているため、税込価格での計算がされる
                        $carry += $product['unit_amount']['value'] * $product['quantity'];
                        return $carry;
                    })),
                ],
                'shipping' => [
                    'currency_code' => 'JPY',
                    'value' => $this->client->roundedCurrencyFormat($order->getDeliveryFeeTotal()),
                ],
                'discount' => [
                    'currency_code' => 'JPY',
                    'value' => $this->client->roundedCurrencyFormat($order->getDiscount()),
                ],
                // 税を送らない
                //'tax_total' => [
                //    'currency_code' => 'JPY',
                //    'value' => $this->client->roundedCurrencyFormat(array_reduce($products, function ($carry, array $product): int {
                //        $carry += $product['tax']['value'] * $product['quantity'];
                //        return $carry;
                //    }))
                //]
            ],
        ];
        $options->items = $products;
    }

    /**
     * 会員情報を付与する
     *
     * @param stdClass $options
     * @param Order $Order
     */
    private function setCustomer(stdClass &$options, Order $Order): void
    {
        $options->customer = [
            'name01' => $Order->getName01(),
            'name02' => $Order->getName02(),
            'email' => $Order->getEmail(),
            'phone_number' => $Order->getPhoneNumber(),
            'address' => [
                'address_line_1' => $Order->getAddr02(),
                'address_line_2' => null,
                'admin_area_2' => $Order->getAddr01(),
                'admin_area_1' => $Order->getPref()->getName(),
                'postal_code' => $Order->getPostalCode(),
                'country_code' => 'JP'
            ]
        ];
    }

    /**
     * 商品、価格情報を付与する
     * PayPal管理ツールの税表示がわかりにくいため、現在は税込価格をセットするようにしている。
     *
     * @param stdClass $options
     * @param Order $Order
     */
    private function setItemAndTotalPriceSubscription(stdClass &$options, Order $Order): void
    {
        /** @var array $products */
        $products = array_map(function (OrderItem $item): array {
            return [
                'sku' => $item->getProductCode(),
                'name' => $item->getProductName(),
                'description' => $item->getProductName(),
                'quantity' => $item->getQuantity(),
                // 税込価格を送る
                'price' => $item->getPriceIncTax(),
                // 'price' => $this->client->roundedCurrencyFormat($item->getPrice()),
                'currency' => "JPY",
                'tax' => $this->client->roundedCurrencyFormat($item->getTax()),
            ];
        }, $Order->getProductOrderItems());

        $options->amount = [
            "total" => $this->client->roundedCurrencyFormat($Order->getTotal()),
            "currency" => "JPY",
            "details" => [
                "subtotal" => $this->client->roundedCurrencyFormat(array_reduce($products, function ($carry, array $product): int {
                    $carry += $product['price'] * $product['quantity'];
                    return $carry;
                })),
                // 税を送らない
                //"tax" => $this->client->roundedCurrencyFormat(array_reduce($products, function ($carry, array $product): int {
                //    $carry += $product['tax'] * $product['quantity'];
                //    return $carry;
                //})),
                "shipping" => $this->client->roundedCurrencyFormat($Order->getDeliveryFeeTotal())
            ]
        ];
        $options->items = $products;
    }

    /**
     * @param stdClass $options
     * @param Order $order
     * @param $authenticatedUser
     */
    private function setApplicationContext(stdClass &$options, Order $order, $authenticatedUser): void
    {
        /** @var string $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodClass();
        $options->application_context = [
            'landing_page' => $paymentMethod === BankTransfer::class ? 'BILLING' : 'LOGIN'
        ];
        if ($authenticatedUser) {
            $options->application_context['shipping_preference'] = 'SET_PROVIDED_ADDRESS';
        }
    }

    /**
     * @param stdClass $options
     * @param $authenticatedUser
     * @param Order $Order
     */
    private function setShipping(stdClass &$options, Order $Order, $authenticatedUser): void
    {
        if ($authenticatedUser) {
            /** @var Shipping $Shipping */
            $Shipping = $Order->getShippings()->first();
            $options->shipping = [
                'name' => [
                    'full_name' => "{$Shipping->getName01()} {$Shipping->getName02()}",
                ],
//                "phone_number" => $Shipping->getPhoneNumber(),
//                "email" => $Order->getEmail(),
                'address' => [
                    'address_line_1' => $Shipping->getAddr02(),
                    'address_line_2' => null,
                    'admin_area_2' => $Shipping->getAddr01(),
                    'admin_area_1' => $Shipping->getPref()->getName(),
                    'postal_code' => $Shipping->getPostalCode(),
                    'country_code' => 'JP'
                ]
            ];
        }
    }

    /**
     * @param Shipping $Shipping
     * @param stdClass $options
     */
    private function setShippingAddressSubscription(stdClass &$options, Shipping $Shipping): void
    {
        $options->shipping_address = [
            'recipient_name' => $Shipping->getFullName(),
            'line1' => $Shipping->getAddr02(),
            "line2" => null,
            'city' => $Shipping->getAddr01(),
            'state' => 'JP',
            "phone" => $Shipping->getPhoneNumber(),
            'postal_code' => $Shipping->getPostalCode(),
            'country_code' => 'JP'
        ];
    }
}
