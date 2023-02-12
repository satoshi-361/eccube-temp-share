<?php

namespace Plugin\EccubePaymentLite4\Service\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PointHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\AccessBlockProcessService;
use Plugin\EccubePaymentLite4\Service\ChangeRegularStatusToRePaymentService;
use Plugin\EccubePaymentLite4\Service\CreateSystemErrorResponseService;
use Plugin\EccubePaymentLite4\Service\CreditCardPaymentWithTokenService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrder3Service;
use Plugin\EccubePaymentLite4\Service\IpBlackListService;
use Plugin\EccubePaymentLite4\Service\SaveGmoEpsilonCreditCardExpirationService;
use Plugin\EccubePaymentLite4\Service\UpdateGmoEpsilonOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateNextShippingDateFromRePaymentService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Credit implements PaymentMethodInterface
{
    /**
     * @var SaveGmoEpsilonCreditCardExpirationService
     */
    private $saveGmoEpsilonCreditCardExpirationService;
    /**
     * @var IpBlackListService
     */
    private $ipBlackListService;
    /**
     * @var CreditCardPaymentWithTokenService
     */
    private $creditCardPaymentWithTokenService;
    /**
     * @var AccessBlockProcessService
     */
    private $accessBlockProcessService;
    /**
     * @var Order
     */
    protected $Order;
    /**
     * @var FormInterface
     */
    protected $form;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;
    /**
     * @var RequestReceiveOrder3Service
     */
    private $requestReceiveOrder3Service;
    /**
     * @var CreateSystemErrorResponseService
     */
    private $createSystemErrorResponseService;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;
    /**
     * @var UpdateGmoEpsilonOrderService
     */
    private $updateGmoEpsilonOrderService;
    /**
     * @var ChangeRegularStatusToRePaymentService
     */
    private $changeRegularStatusToRePaymentService;
    /**
     * @var UpdateNextShippingDateFromRePaymentService
     */
    private $updateNextShippingDateFromRePaymentService;
    /**
     * @var PointHelper
     */
    protected $pointHelper;

    public function __construct(
        EccubeConfig $eccubeConfig,
        SaveGmoEpsilonCreditCardExpirationService $saveGmoEpsilonCreditCardExpirationService,
        IpBlackListService $ipBlackListService,
        CreditCardPaymentWithTokenService $creditCardPaymentWithTokenService,
        AccessBlockProcessService $accessBlockProcessService,
        ConfigRepository $configRepository,
        RequestReceiveOrder3Service $requestReceiveOrder3Service,
        CreateSystemErrorResponseService $createSystemErrorResponseService,
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        UpdateGmoEpsilonOrderService $updateGmoEpsilonOrderService,
        ChangeRegularStatusToRePaymentService $changeRegularStatusToRePaymentService,
        UpdateNextShippingDateFromRePaymentService $updateNextShippingDateFromRePaymentService,
        PointHelper $pointHelper
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->saveGmoEpsilonCreditCardExpirationService = $saveGmoEpsilonCreditCardExpirationService;
        $this->ipBlackListService = $ipBlackListService;
        $this->creditCardPaymentWithTokenService = $creditCardPaymentWithTokenService;
        $this->accessBlockProcessService = $accessBlockProcessService;
        $this->configRepository = $configRepository;
        $this->requestReceiveOrder3Service = $requestReceiveOrder3Service;
        $this->createSystemErrorResponseService = $createSystemErrorResponseService;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->changeRegularStatusToRePaymentService = $changeRegularStatusToRePaymentService;
        $this->updateNextShippingDateFromRePaymentService = $updateNextShippingDateFromRePaymentService;
        $this->pointHelper = $pointHelper;
    }

    /**
     * チェックするレスポンスパラメータのキーを取得
     */
    public function getCheckParameter(): array
    {
        return [
            'trans_code',
            'user_id',
            'result',
            'order_number',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Credit payment process start.');
        $dispatcher = $this->accessBlockProcessService->check();
        // レスポンスがセットされている場合は処理を終了
        if ($dispatcher->getResponse()) {
            return $dispatcher;
        }
        $dispatcher = $this->ipBlackListService->handle();
        // レスポンスがセットされている場合は処理を終了し購入エラー画面を表示
        if ($dispatcher->getResponse()) {
            return $dispatcher;
        }
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $OrderStatus = $this
            ->orderStatusRepository
            ->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());
        // if customer use point =>  rollback point of customer
        if ($this->Order->getUsePoint() > 0) {
            // 利用したポイントをユーザに戻す.
            $this->pointHelper->rollback($this->Order, $this->Order->getUsePoint());
        }
        // トークン決済の場合は以下の処理を行う
        if ($Config->getCreditPaymentSetting() === Config::TOKEN_PAYMENT) {
            $request = Request::createFromGlobals();

            return $this->creditCardPaymentWithTokenService->handle(
                $request->request->get('token'),
                $this->eccubeConfig['gmo_epsilon']['st_code']['credit'],
                $dispatcher,
                $this->Order
            );
        }

        $dispatcher = new PaymentDispatcher();
        $results = $this
            ->requestReceiveOrder3Service
            ->handle(
                $this->Order,
                $this->eccubeConfig['gmo_epsilon']['st_code']['credit']
            );
        if ($results['status'] === 'NG') {
            logs('gmo_epsilon')->error('ERR_CODE = '.$results['err_code']);
            logs('gmo_epsilon')->error('ERR_DETAIL = '.$results['message']);
            $response = $this->createSystemErrorResponseService->get(
                trans('gmo_epsilon.front.shopping.error'),
                $results['message']
            );

            return $dispatcher->setResponse($response);
        }
        $response = new RedirectResponse($results['url']);

        return $dispatcher->setResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(): PaymentResult
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $result = new PaymentResult();
        // リンク決済の場合はパラメーターのチェック処理を行う
        if ($Config->getCreditPaymentSetting() === Config::LINK_PAYMENT) {
            $request = Request::createFromGlobals();
            if (!$this->checkParameter($request, $this->getCheckParameter())) {
                logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().' 不正なGETリクエストを受信しました。');
                $result->setErrors([
                    '不正なリクエストを受信しました。',
                ]);

                return $result;
            }
            $transCode = $request->get('trans_code');
            $gmoEpsilonOrderNo = $request->get('order_number');
        } else {
            $transCode = $this->Order->getTransCode();
            $gmoEpsilonOrderNo = $this->Order->getGmoEpsilonOrderNo();
        }
        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $this->Order,
                $transCode,
                $gmoEpsilonOrderNo
            );
        if (!empty($this->Order->getCustomer())) {
            $this->saveGmoEpsilonCreditCardExpirationService->handle();
            $this->changeRegularStatusToRePaymentService->handle($this->Order->getCustomer());
            $this->updateNextShippingDateFromRePaymentService->update($this->Order->getCustomer());
            if ($this->Order->getUsePoint() > 0) {
                // ユーザの保有ポイントを減算
                $this->pointHelper->prepare($this->Order, $this->Order->getUsePoint());
            }
        }

        $result->setSuccess(true);
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Credit payment process end.');

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(): PaymentResult
    {
        $PaymentResult = new PaymentResult();

        return $PaymentResult->setSuccess(true);
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form): void
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order): void
    {
        $this->Order = $Order;
    }

    private function checkParameter($request, $arrCheckParameter): bool
    {
        foreach ($arrCheckParameter as $key) {
            if (empty($request->get($key)) && $request->get($key) !== '0') {
                return false;
            }
        }

        return true;
    }
}
