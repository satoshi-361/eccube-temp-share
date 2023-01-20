<?php

namespace Plugin\PayPalCheckout\Controller\Admin\Subscription;

use DateTime;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Exception;
use Plugin\PayPalCheckout\Controller\OrderManagement\InitializeOrderController;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Entity\SubscribingCustomer;
use Plugin\PayPalCheckout\Entity\Transaction;
use Plugin\PayPalCheckout\Exception\OrderInitializationException;
use Plugin\PayPalCheckout\Exception\PayPalRequestException;
use Plugin\PayPalCheckout\Exception\ReFoundSubscriptionPaymentException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Repository\SubscribingCustomerRepository;
use Plugin\PayPalCheckout\Service\LoggerService;
use Plugin\PayPalCheckout\Service\Method\Subscription;
use Plugin\PayPalCheckout\Service\PayPalService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SubscriptionPaymentApiController
 * @package Plugin\PayPalCheckout\Controller\Admin\Subscription
 */
class SubscriptionPaymentApiController extends InitializeOrderController
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
     * @var SubscribingCustomerRepository
     */
    protected $subscribingCustomerRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * SubscriptionPaymentApiController constructor.
     * @param CartService $cartService
     * @param MailService $mailService
     * @param OrderHelper $orderHelper
     * @param PurchaseFlow $cartPurchaseFlow
     * @param PayPalService $paypal
     * @param LoggerService $loggerService
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param PrefRepository $prefRepository
     * @param SubscribingCustomerRepository $subscribingCustomerRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param ConfigRepository $configRepository
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
        PrefRepository $prefRepository,
        SubscribingCustomerRepository $subscribingCustomerRepository,
        OrderStatusRepository $orderStatusRepository,
        ConfigRepository $configRepository
    ) {
        parent::__construct($cartService, $mailService, $orderRepository, $orderHelper);
        $this->paypal = $paypal;
        $this->logger = $loggerService;
        $this->cartPurchaseFlow = $cartPurchaseFlow;
        $this->paymentRepository = $paymentRepository;
        $this->prefRepository = $prefRepository;
        $this->subscribingCustomerRepository = $subscribingCustomerRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/paypal/subscription/{id}", requirements={"id" = "\d+"}, name="admin_paypal_subscription")
     * @param Request $request
     * @param SubscribingCustomer $subscribingCustomer
     * @return JsonResponse
     * @throws OrderInitializationException
     * @throws ReFoundSubscriptionPaymentException
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function index(Request $request, SubscribingCustomer $subscribingCustomer): JsonResponse
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        if ($subscribingCustomer->getNextPaymentDate() >= new DateTime()) {
            $this->handleError($subscribingCustomer, '次回決済日を迎えてから実行してください。');
            return $this->json([], Response::HTTP_BAD_REQUEST);
        }

        $this->logger->debug('OrderingProduct has been received', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all()
        ]);

        /** @var Order $subscriptionOrder */
        $subscriptionOrder = $this->initializeOrder($this->purchaseFlow, $subscribingCustomer);
        $this->entityManager->persist($subscriptionOrder);
        $this->entityManager->flush();

        $orderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $subscriptionOrder->setOrderStatus($orderStatus);
        $this->purchaseFlow->prepare($subscriptionOrder, new PurchaseContext());
        try {
            $this->purchaseFlow->commit($subscriptionOrder, new PurchaseContext());
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
            $subscriptionOrder->setOrderStatus($OrderStatus);
            $this->paypal->subscription($subscriptionOrder, $subscribingCustomer,
                function ($response, Transaction $transaction) use ($subscriptionOrder, $subscribingCustomer) {
                    /** @var Config $config */
                    $config = $this->configRepository->get();

                    // update subscribing customer
                    $subscribingCustomer->setReferenceTransaction($transaction);
                    $date = $subscribingCustomer->calculateNextPaymentDate($config->getReferenceDay(), $config->getCutOffDay());
                    $subscribingCustomer->setNextPaymentDate($date);
                    $subscribingCustomer->setErrorMessage(null);
                    $this->entityManager->persist($subscribingCustomer);

                    // create order
                    $subscriptionOrder->setOrderNo($subscriptionOrder->getId());
                    $subscriptionOrder->setPaymentDate(new DateTime());
                    $this->entityManager->persist($subscriptionOrder);
                    $this->entityManager->flush();
                }
            );
        } catch (PayPalRequestException $e) {
            $this->handleError($subscribingCustomer, $e->getMessage());
            return $this->json([], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (ReFoundSubscriptionPaymentException $e) {
            $this->handleError($subscribingCustomer, $e->getMessage());
            throw $e;
        }
        return $this->json([], Response::HTTP_OK);
    }

    /**
     * @param SubscribingCustomer $subscribingCustomer
     * @param string $message
     */
    private function handleError(SubscribingCustomer $subscribingCustomer, string $message): void
    {
        $errors = $this->session->get('paypal.errors', []);
        $errors[$subscribingCustomer->getId()] = $message;
        $this->session->set('paypal.errors', $errors);
    }

    /**
     * @param PurchaseFlow $cartPurchaseFlow
     * @param SubscribingCustomer $subscribingCustomer
     * @return Order
     * @throws OrderInitializationException
     */
    private function initializeOrder(PurchaseFlow $cartPurchaseFlow, SubscribingCustomer $subscribingCustomer): Order
    {
        try {
            /** @var Order $Order */
            $Order = $subscribingCustomer->getReferenceTransaction()->getOrder();

            /** @var Customer $Customer */
            $Customer = $subscribingCustomer->getPrimaryCustomer($this->prefRepository)
                ?? $subscribingCustomer->getCustomer();

            return parent::initializeSubscriptionOrder($cartPurchaseFlow, $Order, $Customer, function (Order $Order): void {
                /** @var Payment $Payment */
                $Payment = $this->paymentRepository->findOneBy([
                    'method_class' => Subscription::class
                ]);
                $Order->setPayment($Payment);
                $Order->setPaymentMethod($Payment->getMethod());
                $this->entityManager->persist($Order);
            });
        } catch (Exception $e) {
            throw new OrderInitializationException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
