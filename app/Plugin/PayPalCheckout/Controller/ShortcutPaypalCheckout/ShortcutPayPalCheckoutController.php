<?php

namespace Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Exception;
use Plugin\PayPalCheckout\Controller\OrderManagement\InitializeOrderController;
use Plugin\PayPalCheckout\Exception\NotFoundCartException;
use Plugin\PayPalCheckout\Exception\OrderInitializationException;
use Plugin\PayPalCheckout\Service\LoggerService;
use Plugin\PayPalCheckout\Service\Method\CreditCard;
use Plugin\PayPalCheckout\Service\PayPalService;

/**
 * Class ShortcutPayPalCheckoutController
 * @package Plugin\PayPalCheckout\Controller\ShortcutPaypalCheckout
 */
class ShortcutPayPalCheckoutController extends InitializeOrderController
{
    /**
     * @var PurchaseFlow
     */
    protected $cartPurchaseFlow;

    /**
     * @var PayPalService
     */
    protected $paypal;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * CreateShortcutOrderController constructor.
     * @param CartService $cartService
     * @param MailService $mailService
     * @param OrderHelper $orderHelper
     * @param PurchaseFlow $cartPurchaseFlow
     * @param PayPalService $paypal
     * @param LoggerService $loggerService
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param PrefRepository $prefRepository
     */
    public function __construct(
        CartService $cartService,
        MailService $mailService,
        OrderHelper $orderHelper,
        PurchaseFlow $cartPurchaseFlow,
        PayPalService $paypal,
        LoggerService $loggerService,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        PrefRepository $prefRepository
    ) {
        parent::__construct($cartService, $mailService, $orderRepository, $orderHelper);
        $this->paypal = $paypal;
        $this->logger = $loggerService;
        $this->cartPurchaseFlow = $cartPurchaseFlow;
        $this->paymentRepository = $paymentRepository;
        $this->prefRepository = $prefRepository;
    }

    /**
     * @param $cart_key
     * @throws NotFoundCartException
     */
    protected function lockCart($cart_key): void
    {
        $Carts = $this->cartService->getCart();
        if (!is_object($Carts)) {
            throw new NotFoundCartException();
        }
        $this->cartService->setPrimary($cart_key);
        $this->cartService->save();
    }

    /**
     * @param PurchaseFlow $cartPurchaseFlow
     * @return void
     * @throws OrderInitializationException
     */
    protected function initializeOrder(PurchaseFlow $cartPurchaseFlow): void
    {
        try {
            parent::eccubeShoppingControllerIndex($cartPurchaseFlow, function (Order $Order): void {
                /** @var Payment $Payment */
                $Payment = $this->paymentRepository->findOneBy([
                    'method_class' => CreditCard::class
                ]);
                $Order->setPayment($Payment);
                $Order->setPaymentMethod($Payment->getMethod());
                $this->entityManager->persist($Order);
            });
        } catch (Exception $e) {
            throw new OrderInitializationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Customer $customer
     */
    protected function initializeCustomer(Customer $customer): void
    {
        $this->session->set(OrderHelper::SESSION_NON_MEMBER, $customer);
        $this->session->set(OrderHelper::SESSION_NON_MEMBER_ADDRESSES, serialize([]));
    }
}
