<?php

namespace Plugin\PayPalCheckout\Service;

use DateTime;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Cart;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Entity\Shipping;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Exception;
use Plugin\PayPalCheckout\Contracts\EccubeAddressAccessible;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Exception\NotFoundBillingTokenException;
use Plugin\PayPalCheckout\Exception\NotFoundOrderingIdException;
use Plugin\PayPalCheckout\Exception\NotFoundPurchaseProcessingOrderException;
use Plugin\PayPalCheckout\Exception\OtherPaymentMethodException;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Service\Method\Acdc;
use Plugin\PayPalCheckout\Service\Method\BankTransfer;
use Plugin\PayPalCheckout\Service\Method\CreditCard;
use Plugin\PayPalCheckout\Service\Method\InlineGuest;
use stdClass;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PayPalOrderService
 * @package Plugin\PayPalCheckout\Service
 */
class PayPalOrderService
{
    const SESSION_SHORTCUT = 'createOrderRequest.ShortcutPaypalCheckout';
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var stdClass
     */
    protected $properties;

    /**
     * @var LoggerService
     */
    protected $logger;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * PayPalOrderService constructor.
     * @param SessionInterface $session
     * @param PrefRepository $prefRepository
     * @param LoggerService $loggerService
     * @param EccubeConfig $eccubeConfig
     * @param CartService $cartService
     * @param OrderHelper $orderHelper
     * @param Router $router
     * @param PaymentRepository $paymentRepository
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        SessionInterface $session,
        PrefRepository $prefRepository,
        LoggerService $loggerService,
        EccubeConfig $eccubeConfig,
        CartService $cartService,
        OrderHelper $orderHelper,
        RouterInterface $router,
        PaymentRepository $paymentRepository,
        ConfigRepository $configRepository
    )
    {
        $this->session = $session;
        $this->prefRepository = $prefRepository;
        $this->logger = $loggerService;
        $this->eccubeConfig = $eccubeConfig;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->router = $router;
        $this->paymentRepository = $paymentRepository;
        $this->Config = $configRepository->get();
    }

    /**
     * @return bool
     */
    public function isProcessingShortcutPayment(): bool
    {
        return $this->router->getContext()->getPathInfo() === "/paypal_confirm"
            && !is_null($this->session->get(self::SESSION_SHORTCUT, null));
    }

    /**
     * @param $anything
     */
    public function setShortcutPaymentSession($anything): void
    {
        $this->session->set(self::SESSION_SHORTCUT, $anything);
    }

    /**
     * @return string
     */
    public function getOrderingId(): string
    {
        return $this->session->get('createOrderRequest.orderingId', '');
    }

    /**
     * @param string $orderingId
     */
    public function setOrderingId(string $orderingId): void
    {
        $this->session->set('createOrderRequest.orderingId', $orderingId);
    }

    /**
     * @param Shipping $shipping
     */
    public function setShippingAddress(Shipping $shipping): void
    {
        $this->session->set('createOrderRequest.shippingAddress', $shipping);
    }

    /**
     * @param string $billingToken
     */
    public function setBillingToken(string $billingToken): void
    {
        $this->session->set('createOrderRequest.billingToken', $billingToken);
    }

    /**
     * @return Order
     * @throws NotFoundPurchaseProcessingOrderException
     */
    public function getPurchaseProcessingOrder(): Order
    {
        /** @var string|null $preOrderId */
        $preOrderId = $this->cartService->getPreOrderId();
        if (is_null($preOrderId)) {
            throw new NotFoundPurchaseProcessingOrderException();
        }

        /** @var Order|null $order */
        $order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (is_null($order)) {
            throw new NotFoundPurchaseProcessingOrderException();
        }

        if ($preOrderId === $order->getPreOrderId()) {
            return $order;
        }
        throw new NotFoundPurchaseProcessingOrderException();
    }

    /**
     * @param callable $callback
     * @return void
     * @throws NotFoundOrderingIdException
     */
    public function doProcessOrderingId(callable $callback): void
    {
        try {
            /** @var string|null $orderingId */
            $orderingId = $this->session->get('createOrderRequest.orderingId', null);
            if (is_null($orderingId)) {
                throw new NotFoundOrderingIdException();
            }
            call_user_func($callback, $orderingId);
        } finally {
            $this->session->remove('createOrderRequest.orderingId');
        }
    }

    /**
     * @param callable $callback
     * @return void
     * @throws NotFoundBillingTokenException
     */
    public function doProcessBillingToken(callable $callback): void
    {
        try {
            /** @var string|null $billingToken */
            $billingToken = $this->session->get('createOrderRequest.billingToken', null);
            if (is_null($billingToken)) {
                throw new NotFoundBillingTokenException();
            }
            call_user_func($callback, $billingToken);
        } finally {
            $this->session->remove('createOrderRequest.billingToken');
        }
    }

    /**
     * @return array
     */
    private function getPrefList(): array
    {
        return [
            "hokkaido" => "北海道",
            "aomori" => "青森県",
            "iwate" => "岩手県",
            "miyagi" => "宮城県",
            "akita" => "秋田県",
            "yamagata" => "山形県",
            "fukushima" => "福島県",
            "ibaraki" => "茨城県",
            "tochigi" => "栃木県",
            "gunma" => "群馬県",
            "saitama" => "埼玉県",
            "chiba" => "千葉県",
            "tokyo" => "東京都",
            "kanagawa" => "神奈川県",
            "niigata" => "新潟県",
            "toyama" => "富山県",
            "ishikawa" => "石川県",
            "fukui" => "福井県",
            "yamanashi" => "山梨県",
            "nagano" => "長野県",
            "gifu" => "岐阜県",
            "shizuoka" => "静岡県",
            "aichi" => "愛知県",
            "mie" => "三重県",
            "shiga" => "滋賀県",
            "kyoto" => "京都府",
            "osaka" => "大阪府",
            "hyogo" => "兵庫県",
            "nara" => "奈良県",
            "wakayama" => "和歌山県",
            "tottori" => "鳥取県",
            "shimane" => "島根県",
            "okayama" => "岡山県",
            "hiroshima" => "広島県",
            "yamaguchi" => "山口県",
            "tokushima" => "徳島県",
            "kagawa" => "香川県",
            "ehime" => "愛媛県",
            "kochi" => "高知県",
            "fukuoka" => "福岡県",
            "saga" => "佐賀県",
            "nagasaki" => "長崎県",
            "kumamoto" => "熊本県",
            "oita" => "大分県",
            "miyazaki" => "宮崎県",
            "kagoshima" => "鹿児島県",
            "okinawa" => "沖縄県",
        ];
    }

    /**
     * @param $pref
     * @return string
     */
    public function getPrefByPayPalPref($pref)
    {
        $pattern = '/.*[都道府県]/';
        if (preg_match($pattern, $pref, $match)) {
            return $match[0];
        } else {
            /** @var array $prefList */
            $prefList = $this->getPrefList();
            $index = strtolower($pref);
            $key = preg_grep("/^{$index}/", array_keys($prefList));
            return $prefList[current($key)];
        }
    }

    /**
     * @param EccubeAddressAccessible $reader
     * @return Customer
     * @throws Exception
     */
    public function generateCustomer(EccubeAddressAccessible $reader): Customer
    {
        /** @var array $data */
        $data = $reader->getEccubeAddressFormat();

        /** @var Pref|null $pref */
        $pref = $this->prefRepository->findOneBy([
            'name' => $this->getPrefByPayPalPref($data['pref'])
        ]);
        if (is_null($pref)) {
            throw new PayPalCheckoutException();
        }

        /** @var Customer $customer */
        $customer = new Customer();
        $customer
            ->setName01($data['name01'])
            ->setName02($data['name02'])
            ->setKana01($data['kana01'])
            ->setKana02($data['kana02'])
            ->setCompanyName($data['company_name'])
            ->setEmail($data['email'])
            ->setPhonenumber($data['phone_number'])
            ->setPostalcode($data['postal_code'])
            ->setPref($pref)
            ->setAddr01($data['addr01'])
            ->setAddr02($data['addr02'])
            ->setUpdateDate(new DateTime());
        return $customer;
    }

    /**
     * @return string
     */
    public function generateScriptSubscription(): string
    {
        return $this->generateScriptUrl([
            "vault" => "true"
        ]);
    }

    /**
     * @return string
     */
    public function generateScriptShortcutSubscriptionPayPalCheckout(): string
    {
        return $this->generateScriptUrl([
            "commit" => "false",
            "vault" => "true"
        ]);
    }

    /**
     * @return string
     */
    public function generateScriptShortcutPayPalCheckout(): string
    {
        return $this->generateScriptUrl([
            "commit" => "false"
        ]);
    }

    /**
     * @return string
     */
    public function generateScriptPayPalCheckout(): string
    {
        return $this->generateScriptUrl();
    }

    /**
     * @param array $options
     * @return string
     */
    public function generateScriptUrl(array $options = []): string
    {
        /** @var string $clientId */
        $clientId = $this->Config->getClientId();

        /** @var string $queryParameters */
        $queryParameters = http_build_query(array_merge([
            'client-id' => $clientId,
            'currency' => $this->eccubeConfig->get('paypal.currency') ?? "JPY",
            'locale' => $this->eccubeConfig->get('paypal.locale') ?? "ja_JP",
            'integration-date' => $this->eccubeConfig->get('paypal.integration-date') ?? "2019-07-23",
            'commit' => "false"
        ], $options));

        /** @var string $scriptUrl */
        $scriptUrl = "{$this->eccubeConfig->get('paypal.sdk.url')}?${queryParameters}";
        return $scriptUrl;
    }

    /**
     * 非ログインでもお客様情報を入力されている場合は true になることに注意
     *
     * @return bool
     */
    private function isAuthenticatedUser(): bool
    {
        return !$this->orderHelper->isLoginRequired();
    }

    /**
     * 非ログインでもお客様情報が入力されている場合は false になることに注意
     *
     * @return bool
     */
    private function isGuestUser(): bool
    {
        return !$this->isAuthenticatedUser();
    }

    /**
     * @param Cart $Cart
     * @return array
     * @throws PayPalCheckoutException
     */
    public function generateFrontEndParametersOnCartIndexPage(Cart $Cart): array
    {
        switch (true) {
            case $this->isAuthenticatedUser() && PayPalService::existsSubscriptionProductInCart($Cart):
                /** @var string|null $cartKey */
                $cartKey = $Cart->getCartKey();
                if (is_null($Cart->getCartKey())) {
                    throw new PayPalCheckoutException();
                }
                $snippet = '@PayPalCheckout/default/Cart/index/subscription.twig';
                $parameters['PayPalCheckout']['paypal_shortcut_ordering_subscription_product_url'] = $this->router->generate('paypal_shortcut_ordering_subscription_product', ['cart_key' => $cartKey], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['next_page_url'] = $this->router->generate('paypal_confirm', [], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['widget_url'] = $this->generateScriptShortcutSubscriptionPayPalCheckout();
                break;
            case $this->isAuthenticatedUser():
                /** @var string|null $cartKey */
                $cartKey = $Cart->getCartKey();
                if (is_null($Cart->getCartKey())) {
                    throw new PayPalCheckoutException();
                }
                $snippet = '@PayPalCheckout/default/Cart/index/checkout.twig';
                $parameters['PayPalCheckout']['paypal_shortcut_prepare_transaction_url'] = $this->router->generate('paypal_shortcut_prepare_transaction', ['cart_key' => $cartKey], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['next_page_url'] = $this->router->generate('paypal_confirm', [], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['widget_url'] = $this->generateScriptShortcutPayPalCheckout();
                break;
            case $this->isGuestUser() && PayPalService::existsSubscriptionProductInCart($Cart):
                $snippet = '@PayPalCheckout/default/Cart/index/nothing.twig';
                $parameters['PayPalCheckout']['widget_url'] = null;
                break;
            case $this->isGuestUser():
                /** @var string|null $cartKey */
                $cartKey = $Cart->getCartKey();
                if (is_null($Cart->getCartKey())) {
                    throw new PayPalCheckoutException();
                }
                $snippet = '@PayPalCheckout/default/Cart/index/guest.twig';
                $parameters['PayPalCheckout']['guest_paypal_shortcut_prepare_transaction_url'] = $this->router->generate('guest_paypal_shortcut_prepare_transaction', ['cart_key' => $cartKey], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['guest_paypal_order_url'] = $this->router->generate('guest_paypal_order', ['cart_key' => $cartKey], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['next_page_url'] = $this->router->generate('paypal_confirm', [], UrlGeneratorInterface::ABSOLUTE_PATH);
                $parameters['PayPalCheckout']['widget_url'] = $this->generateScriptShortcutPayPalCheckout();
                break;
            default:
                $snippet = '@PayPalCheckout/default/Cart/index/nothing.twig';
                $parameters['PayPalCheckout']['widget_url'] = null;
        }
        return [$snippet, $parameters];
    }

    /**
     * @param Order $order
     * @return array
     * @throws OtherPaymentMethodException
     */
    public function generateFrontEndParametersOnShoppingConfirmPage(Order $order): array
    {
        /** @var array $parameters */
        $parameters = [];

        /** @var string|null $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodClass();
        if (!($paymentMethod === BankTransfer::class || $paymentMethod === CreditCard::class || $paymentMethod === InlineGuest::class)) {
            throw new OtherPaymentMethodException();
        }

        if (PayPalService::existsSubscriptionProductInOrderItems($order)) {
            $snippet = '@PayPalCheckout/default/Shopping/confirm/subscription.twig';
            $widget_url = $this->generateScriptSubscription();
            $api_url = $this->router->generate('paypal_prepare_subscription');
        } else {
            $snippet = '@PayPalCheckout/default/Shopping/confirm/checkout.twig';
            $widget_url = $this->generateScriptPayPalCheckout();
            $api_url = $this->router->generate('paypal_prepare_transaction');
        }
        $parameters['PayPalCheckout']['widget_url'] = $widget_url;
        $parameters['PayPalCheckout']['api_url'] = $api_url;
        return [$snippet, $parameters];
    }

    /**
     * @param Cart $Cart
     * @return array
     * @throws PayPalCheckoutException
     */
    public function generateFrontEndParametersOnShoppingLoginPage(Cart $Cart): array
    {
        /** @var string|null $cartKey */
        $cartKey = $Cart->getCartKey();
        if (is_null($Cart->getCartKey())) {
            throw new PayPalCheckoutException();
        }

        $snippet = '@PayPalCheckout/default/Shopping/login/shortcut.twig';
        $parameters['PayPalCheckout']['guest_paypal_shortcut_prepare_transaction_url'] = $this->router->generate('guest_paypal_shortcut_prepare_transaction', ['cart_key' => $cartKey], UrlGeneratorInterface::ABSOLUTE_PATH);
        $parameters['PayPalCheckout']['guest_paypal_order_url'] = $this->router->generate('guest_paypal_order', ['cart_key' => $cartKey], UrlGeneratorInterface::ABSOLUTE_PATH);
        $parameters['PayPalCheckout']['next_page_url'] = $this->router->generate('paypal_confirm', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $parameters['PayPalCheckout']['widget_url'] = $this->generateScriptShortcutPayPalCheckout();

        return [$snippet, $parameters];
    }

    /**
     * @return array
     */
    public function generateFrontEndParametersOnShoppingIndexPage(): array
    {
        /** @var Payment $BankTransferPayment */
        $PaymentBankTransfer = $this->paymentRepository->findOneBy([
            'method_class' => BankTransfer::class
        ], []);
        /** @var Payment $BankTransferPayment */
        $PaymentCreditCard = $this->paymentRepository->findOneBy([
            'method_class' => CreditCard::class
        ], []);
        /** @var Payment $PaymentInlineGuest */
        $PaymentInlineGuest = $this->paymentRepository->findOneBy([
            'method_class' => InlineGuest::class
        ], []);
        /** @var Payment $PaymentAcdc */
        $PaymentAcdc = $this->paymentRepository->findOneBy([
            'method_class' => Acdc::class
        ], []);

        $snippet = '@PayPalCheckout/default/Shopping/index/common.twig';
        $parameters['PayPalCheckout']['bank_transfer_id'] = "#shopping_order_Payment_{$PaymentBankTransfer->getId()}";
        $parameters['PayPalCheckout']['credit_card_id'] = "#shopping_order_Payment_{$PaymentCreditCard->getId()}";
        $parameters['PayPalCheckout']['inline_guest_id'] = "#shopping_order_Payment_{$PaymentInlineGuest->getId()}";
        $parameters['PayPalCheckout']['acdc_id'] = "#shopping_order_Payment_{$PaymentAcdc->getId()}";

        $imgBankTransferPath = __DIR__ . '/../Resource/assets/img/bank_transfer_appearance.png';
        $imgCreditCardPath = __DIR__ . '/../Resource/assets/img/credit_card_appearance.png';
        $imgInlineGuestPath = __DIR__ . '/../Resource/assets/img/inline_guest_appearance.png';
        $imgAcdcPath = __DIR__ . '/../Resource/assets/img/acdc_appearance.png';

        $paymentBannerImageUrl = $this->getPaymentBannerImageUrl();
        $base64BankTransfer = base64_encode(file_get_contents($imgBankTransferPath));
        $base64CreditCard = base64_encode(file_get_contents($imgCreditCardPath));
        $base64InlineGuest = base64_encode(file_get_contents($imgInlineGuestPath));
        $base64Acdc = base64_encode(file_get_contents($imgAcdcPath));

        $parameters['PayPalCheckout']['credit_card_appearance_base64'] = "data:image/png;base64,{$base64CreditCard}";
        $parameters['PayPalCheckout']['bank_transfer_appearance_base64'] = "data:image/png;base64,{$base64BankTransfer}";
        $parameters['PayPalCheckout']['inline_guest_appearance_base64'] = "data:image/png;base64,{$base64InlineGuest}";
        $parameters['PayPalCheckout']['acdc_appearance_base64'] = "data:image/png;base64,{$base64Acdc}";
        $parameters['PayPalCheckout']['payment_banner_image_url'] = $paymentBannerImageUrl;
        $parameters['PayPalCheckout']['widget_url'] = $this->generateScriptPayPalCheckout();
        $parameters['PayPalCheckout']['api_url'] = $this->router->generate('paypal_prepare_transaction');
        $parameters['PayPalCheckout']['inline_guest_api_url'] = $this->router->generate('paypal_prepare_transaction_inline_guest');
        $parameters['PayPalCheckout']['acdc_api_url'] = $this->router->generate('paypal_prepare_transaction_acdc');

        $parameters['PayPalCheckout']['bank_transfer_label'] = $PaymentBankTransfer->getMethod();
        $parameters['PayPalCheckout']['credit_card_label'] = $PaymentCreditCard->getMethod();
        $parameters['PayPalCheckout']['inline_guest_label'] = $PaymentInlineGuest->getMethod();
        $parameters['PayPalCheckout']['acdc_label'] = $PaymentAcdc->getMethod();


        return [$snippet, $parameters];
    }

    /**
     * @return string
     */
    private function getPaymentBannerImageUrl(): string
    {
        /** @var string $selectedNumber */
        $selectedNumber = $this->Config->getPaymentPaypalLogo();
        return $this->eccubeConfig->get("paypal.paypal_express_payment_paypal_logo_${selectedNumber}");
    }
}
