<?php

namespace Plugin\PayPalCheckout\Controller\OrderManagement;

use Eccube\Controller\AbstractShoppingController as EccubeAbstractShoppingController;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\CustomerAddress;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;

/**
 * Class InitializeOrderController
 * @package Plugin\PayPalCheckout\Controller\ShippingOrder
 */
class InitializeOrderController extends EccubeAbstractShoppingController
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * ShoppingController constructor.
     * @param CartService $cartService
     * @param MailService $mailService
     * @param OrderRepository $orderRepository
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        CartService $cartService,
        MailService $mailService,
        OrderRepository $orderRepository,
        OrderHelper $orderHelper
    ) {
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param PurchaseFlow $cartPurchaseFlow
     * @param callable $setupShippingOrderAfterInitialization
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function eccubeShoppingControllerIndex(PurchaseFlow $cartPurchaseFlow, callable $setupShippingOrderAfterInitialization)
    {
        // ログイン状態のチェック.
        if ($this->orderHelper->isLoginRequired()) {
            log_info('[注文手続] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');

            return $this->redirectToRoute('shopping_login');
        }

        // カートチェック.
        $Cart = $this->cartService->getCart();
        if (!($Cart && $this->orderHelper->verifyCart($Cart))) {
            log_info('[注文手続] カートが購入フローへ遷移できない状態のため, カート画面に遷移します.');

            return $this->redirectToRoute('cart');
        }

        // 受注の初期化.
        log_info('[注文手続] 受注の初期化処理を開始します.');
        $Customer = $this->getUser() ? $this->getUser() : $this->orderHelper->getNonMember();
        $Order = $this->orderHelper->initializeOrder($Cart, $Customer);

        // 受注の初期化後の処理
        call_user_func($setupShippingOrderAfterInitialization, $Order);

        // 集計処理.
        log_info('[注文手続] 集計処理を開始します.', [$Order->getId()]);
        $flowResult = $this->executePurchaseFlow($Order, false);
        $this->entityManager->flush();

        if ($flowResult->hasError()) {
            log_info('[注文手続] Errorが発生したため購入エラー画面へ遷移します.', [$flowResult->getErrors()]);

            return $this->redirectToRoute('shopping_error');
        }

        if ($flowResult->hasWarning()) {
            log_info('[注文手続] Warningが発生しました.', [$flowResult->getWarning()]);

            // 受注明細と同期をとるため, CartPurchaseFlowを実行する
            $cartPurchaseFlow->validate($Cart, new PurchaseContext());
            $this->cartService->save();
        }

        // マイページで会員情報が更新されていれば, Orderの注文者情報も更新する.
        if ($Customer->getId()) {
            $this->orderHelper->updateCustomerInfo($Order, $Customer);
            $this->entityManager->flush();
        }

        $form = $this->createForm(OrderType::class, $Order);

        return [
            'form' => $form->createView(),
            'Order' => $Order,
        ];
    }

    /**
     * @param PurchaseFlow $cartPurchaseFlow
     * @param Order $ReferenceOrder
     * @param Customer $RefCustomer
     * @param callable $setupShippingOrderAfterInitialization
     * @return Order
     */
    protected function initializeSubscriptionOrder(PurchaseFlow $cartPurchaseFlow, Order $ReferenceOrder, Customer $RefCustomer, callable $setupShippingOrderAfterInitialization): Order
    {
        /** @var array $items */
        $items = array_map(function (OrderItem $item): CartItem {
            $cartItem = new CartItem();
            $cartItem->setQuantity($item->getQuantity());
            $cartItem->setPrice($item->getProductClass()->getPrice02IncTax());
            $cartItem->setProductClass($item->getProductClass());
            return $cartItem;
        }, $ReferenceOrder->getProductOrderItems());

        /** @var Cart $cart */
        $cart = new Cart();
        $cart = $cart->setCartItems($items);

        /** @var Order $Order */
        $Order = $this->orderHelper->createPurchaseProcessingOrder($cart, $RefCustomer);

        call_user_func($setupShippingOrderAfterInitialization, $Order);

        $flowResult = $this->executePurchaseFlow($Order, false);

        $this->orderHelper->updateCustomerInfo($Order, $RefCustomer);
        $this->entityManager->flush();
        return $Order;
    }

    /**
     * @param PurchaseFlow $cartPurchaseFlow
     * @param Customer $customer
     * @return void
     */
    protected function updateGuestOrder(PurchaseFlow $cartPurchaseFlow, Customer $customer): void
    {
        $preOrderId = $this->cartService->getPreOrderId();
        $order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        $this->orderHelper->updateCustomerInfo($order, $customer);

        /** @var Shipping $shipping */
        $shipping = $order->getShippings()->first();

        /** @var CustomerAddress $customerAddress */
        $customerAddress = new CustomerAddress();
        $customerAddress->setFromCustomer($customer);
        $shipping->setFromCustomerAddress($customerAddress);

        $flowResult = $this->executePurchaseFlow($order, false);
        $this->entityManager->flush();
    }
}
