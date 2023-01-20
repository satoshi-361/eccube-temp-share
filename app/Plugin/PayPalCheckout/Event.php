<?php

namespace Plugin\PayPalCheckout;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Cart;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Service\CartService;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\CacheUtil;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Exception\OtherPaymentMethodException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Service\LoggerService;
use Plugin\PayPalCheckout\Service\Method\CreditCard;
use Plugin\PayPalCheckout\Service\Method\InlineGuest;
use Plugin\PayPalCheckout\Service\Method\Acdc;
use Plugin\PayPalCheckout\Service\PayPalAcdcService;
use Plugin\PayPalCheckout\Service\PayPalOrderService;
use Plugin\PayPalCheckout\Service\PayPalService;
use Plugin\PayPalCheckout\Service\LoginService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class Event
 * @package Plugin\PayPalCheckout
 */
class Event implements EventSubscriberInterface
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var PayPalService
     */
    protected $paypalService;

    /**
     * @var PayPalOrderService
     */
    protected $paypalOrderService;

    /**
     * @var PayPalAcdcService
     */
    protected $paypalAcdcService;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var LoggerService
     */
    protected $logger;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var CacheUtil
     */
    protected $cacheUtil;

    /**
     * @var LoginService
     */
    protected $loginService;

    /**
     * PayPalCheckoutEvent constructor.
     *
     * @param CartService $cartService
     * @param PayPalOrderService $paypalOrderService
     * @param PayPalAcdcService $paypalAcdcService
     * @param EccubeConfig $eccubeConfig
     * @param ConfigRepository $configRepository
     * @param ContainerInterface $container
     * @param LoggerService $loggerService
     * @param SessionInterface $session
     * @param PaymentRepository $paymentRepository
     * @param LoginService $loginService
     */
    public function __construct(
        CartService $cartService,
        PayPalService $payPalService,
        PayPalOrderService $paypalOrderService,
        PayPalAcdcService $paypalAcdcService,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        ContainerInterface $container,
        LoggerService $loggerService,
        SessionInterface $session,
        PaymentRepository $paymentRepository,
        CacheUtil $cacheUtil,
        LoginService $loginService)
    {
        $this->cartService = $cartService;
        $this->paypalService = $payPalService;
        $this->paypalOrderService = $paypalOrderService;
        $this->paypalAcdcService = $paypalAcdcService;
        $this->container = $container;
        $this->eccubeConfig = $eccubeConfig;
        $this->Config = $configRepository->get();
        $this->logger = $loggerService;
        $this->loginService = $loginService;
        $this->session = $session;
        $this->paymentRepository = $paymentRepository;
        $this->cacheUtil = $cacheUtil;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopping/login.twig' => 'onDefaultShoppingLoginTwig',
            'Shopping/index.twig' => 'onDefaultShoppingIndexTwig',
            'Shopping/confirm.twig' => 'onDefaultShoppingConfirmTwig',
            'Cart/index.twig' => 'onDefaultCartIndexTwig',
            'Block/paypal_logo.twig' => 'onDefaultPayPalLogoTwig',
            EccubeEvents::FRONT_SHOPPING_SHIPPING_COMPLETE => 'onChangedShippingAddress',
        ];
    }

    /**
     * @param TemplateEvent $event
     * @throws Exception\PayPalCheckoutException
     */
    public function onDefaultShoppingLoginTwig(TemplateEvent $event): void
    {
        $Carts = $this->cartService->getCart();
        if (is_null($Carts)) {
            return;
        }

        if ($this->paypalService::existsSubscriptionProductInCart($Carts)) {
            return;
        }
        if (!$this->paypalService->useExpressBtn()) {
            return;
        }

        // カート金額が決済可能金額でない場合、PayPalボタンを表示しない
        $amount = $this->paypalService::getCartAmount($Carts);
        if(!$this->isPayableAmount($amount)) {
            return;
        }

        list($snippet, $parameters) = $this->paypalOrderService->generateFrontEndParametersOnShoppingLoginPage($Carts);
        $parameters['PayPalCheckout']['Config'] = $this->Config;

        $this->logger->debug('Generate PayPal frontend parameters on /shipping/login page: ', [
            'snippet' => $snippet,
            'parameters' => $parameters,
        ]);

        $event->addSnippet($snippet);
        $event->addAsset('@PayPalCheckout/default/head.twig');
        $event->setParameters(array_merge($event->getParameters(), $parameters));
    }

    /**
     * @param TemplateEvent $event
     * @throws Exception\PayPalCheckoutException
     */
    public function onDefaultCartIndexTwig(TemplateEvent $event): void
    {
//        $this->session->remove(OrderHelper::SESSION_NON_MEMBER);
//        $this->session->remove(OrderHelper::SESSION_NON_MEMBER_ADDRESSES);

        /** @var array $parameters */
        $parameters = $event->getParameters();
        if (empty($parameters['Carts'])) {
            return;
        }
        if (!$this->paypalService->useExpressBtn()) {
            return;
        }

        /** @var Cart $cart */
        $cart = $parameters['Carts'][0];

        // カート金額が決済可能金額でない場合、PayPalボタンを表示しない
        $amount = $this->paypalService::getCartAmount($cart);
        if(!$this->isPayableAmount($amount)) {
            return;
        }

        list($snippet, $parameters) = $this->paypalOrderService->generateFrontEndParametersOnCartIndexPage($cart);
        $parameters['PayPalCheckout']['Config'] = $this->Config;

        $this->logger->debug('Generate PayPal frontend parameters on /cart page: ', [
            'snippet' => $snippet,
            'parameters' => $parameters,
        ]);

        $event->addSnippet($snippet);
        $event->addAsset('@PayPalCheckout/default/head.twig');
        $event->setParameters(array_merge($event->getParameters(), $parameters));
    }

    /**
     * @param TemplateEvent $event
     * @throws \Exception
     */
    public function onDefaultShoppingIndexTwig(TemplateEvent $event): void
    {
        /** @var array $parameters */
        $parameters = $event->getParameters();
        if (empty($parameters['Order'])) {
            return;
        }
        /** @var Payment $payment */
        $payment = $parameters['Order']->getPayment();

        // 支払い方法が何も選択されてない場合は、処理しない
        if (!isset($payment)) {
            return;
        }

        list($snippet, $parameters) = $this->paypalOrderService->generateFrontEndParametersOnShoppingIndexPage();
        $parameters['PayPalCheckout']['Config'] = $this->Config;
        $parameters['PayPalCheckout']['isLogin'] = $this->loginService->isLoginUser();

        $this->logger->debug('Generate PayPal frontend parameters on /shopping page: ', [
            'snippet' => $snippet,
            'parameters' => $parameters,
        ]);

        $event->addSnippet($snippet);
        $event->addAsset('@PayPalCheckout/default/head.twig');

        if ($payment->getMethodClass() === InlineGuest::class) {
            // inline決済専用のコードを追加で読み込む
            $event->addSnippet('@PayPalCheckout/default/Shopping/index/inline_guest.twig');
            // inline決済の場合、入力が完了するまで「確認する」ボタンを非表示にしておく
            $source = $event->getSource();
            $pattern = '#<button[^>]*class="ec-blockBtn--action"[^>]*>[^<]+</button>#';
            $replacedSource = preg_replace($pattern, '<button class="ec-blockBtn--action" disabled>確認する</button>', $source);
            if (!is_null($replacedSource)) {
                $event->setSource($replacedSource);
            }
        }
        if ($payment->getMethodClass() === Acdc::class) {
            // Acdc決済専用のコードを追加で読み込む
            $event->addSnippet('@PayPalCheckout/default/Shopping/index/acdc.twig');
            // クライアントからAPIアクセスするためのトークンを生成
            $useVault = $this->paypalAcdcService->canUseVault();
            $parameters['PayPalCheckout']['acdcClientToken'] = $this->paypalAcdcService->getClientToken($useVault);
            $parameters['PayPalCheckout']['acdcShowVault'] = $useVault;
            $parameters['PayPalCheckout']['acdcUse3dsecure'] = $this->Config->getUse3dsecure();
            $parameters['PayPalCheckout']['acdcFraudNetSessionIdentifier'] = $this->paypalAcdcService->createAndSaveFraudNetSessionIdentifierToSession();
            $parameters['PayPalCheckout']['acdcSourceWebsiteIdentifier'] = $this->paypalAcdcService->getSourceWebsiteIdentifier();
            // Vault で保存されたクレジットカード情報があれば取得する
            if ($useVault) {
                $vault = $this->paypalAcdcService->getVaults();
                // vault が2件以上存在した場合、不整合なので安全を考慮し全ての vault を削除する
                if (count($vault) >= 2) {
                    $this->paypalAcdcService->bulkDeleteExistingVault();
                    $vault = [];
                }
                $parameters['PayPalCheckout']['acdcVault'] = $vault[0] ?? [];
            }
        }

        $event->setParameters(array_merge($event->getParameters(), $parameters));
    }

    /**
     * @param TemplateEvent $event
     */
    public function onDefaultShoppingConfirmTwig(TemplateEvent $event): void
    {
        /** @var Order $order */
        $order = $event->getParameter('Order');
        $payment = $order->getPayment();
        $isProcessingShortcutPayment = $this->paypalOrderService->isProcessingShortcutPayment();
        try {
            if ($isProcessingShortcutPayment) {
                $this->session->remove(PayPalOrderService::SESSION_SHORTCUT);
                $snippet = '@PayPalCheckout/default/Shopping/confirm/shortcut.twig';
            } else if ($payment->getMethodClass() === InlineGuest::class) {
                $snippet = '@PayPalCheckout/default/Shopping/confirm/inline_guest.twig';
            } else {
                list($snippet, $parameters) = $this->paypalOrderService->generateFrontEndParametersOnShoppingConfirmPage($order);
            }
            $parameters['PayPalCheckout']['Config'] = $this->Config;
        } catch (OtherPaymentMethodException $e) {
            // PayPal決済以外は処理終了
            return;
        }

        // 「注文する」ボタンが一瞬表示されてしまうので、あらかじめ非表示にしておく。
        // 逆にショートカット決済、クレジットカード決済の場合は「注文する」ボタンを使うので処理しない。
        if (!$isProcessingShortcutPayment
            && $payment->getMethodClass() !== InlineGuest::class
            && $payment->getMethodClass() !== Acdc::class) {
            $source = $event->getSource();
            $pattern = '#<button[^>]*class="ec-blockBtn--action"[^>]*>[^<]+</button>#';
            $replacedSource = preg_replace($pattern, '<button class="ec-blockBtn--action" style="opacity: 0" disabled></button>', $source);
            if (!is_null($replacedSource)) {
                $event->setSource($replacedSource);
            }
        }

        $this->logger->debug('Generate PayPal frontend parameters on /shopping/confirm page: ', [
            'custom_data' => [
                'snippet' => $snippet,
                'parameters' => $parameters,
            ],
        ]);

        $event->addSnippet($snippet);
        $event->addAsset('@PayPalCheckout/default/head.twig');
        $event->setParameters(array_merge($event->getParameters(), $parameters));
    }

    /**
     * @param EventArgs $event
     */
    public function onChangedShippingAddress(EventArgs $event): void
    {
        /** @var Shipping $shipping */
        $shipping = $event->getArgument('Shipping');
        $this->paypalOrderService->setShippingAddress($shipping);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onDefaultPayPalLogoTwig(TemplateEvent $event): void
    {
        /** @var string $selectedNumber */
        $selectedNumber = $this->Config->getPaypalLogo();
        $parameters['PayPalCheckout']['paypal_logo'] = $this->eccubeConfig->get("paypal.paypal_express_paypal_logo_${selectedNumber}");
        $event->setParameters(array_merge($event->getParameters(), $parameters));
    }

    private function isPayableAmount($amount): bool
    {
        $payment = $this->paymentRepository->findOneBy(['method_class' => CreditCard::class]);
        if (!empty($payment)) {
            $minRule = $payment->getRuleMin();
            $maxRule = $payment->getRuleMax();
        }
        if(!isset($minRule) || $minRule < PluginManager::MIN_AMOUNT)
            $minRule = PluginManager::MIN_AMOUNT;
        if(!isset($maxRule) || $maxRule > PluginManager::MAX_AMOUNT)
            $maxRule = PluginManager::MAX_AMOUNT;

        if($amount < $minRule || $amount > $maxRule) {
            return false;
        }

        return true;

    }
}
