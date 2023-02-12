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
use Plugin\EccubePaymentLite4\Service\AccessBlockProcessService;
use Plugin\EccubePaymentLite4\Service\ChangeRegularStatusToRePaymentService;
use Plugin\EccubePaymentLite4\Service\CreateSystemErrorResponseService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrder3Service;
use Plugin\EccubePaymentLite4\Service\IpBlackListService;
use Plugin\EccubePaymentLite4\Service\SaveGmoEpsilonCreditCardExpirationService;
use Plugin\EccubePaymentLite4\Service\UpdateGmoEpsilonOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateNextShippingDateFromRePaymentService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Reg_Credit implements PaymentMethodInterface
{
    /**
     * @var IpBlackListService
     */
    private $ipBlackListService;
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
     * @var SaveGmoEpsilonCreditCardExpirationService
     */
    private $saveGmoEpsilonCreditCardExpirationService;
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
        IpBlackListService $ipBlackListService,
        AccessBlockProcessService $accessBlockProcessService,
        RequestReceiveOrder3Service $requestReceiveOrder3Service,
        CreateSystemErrorResponseService $createSystemErrorResponseService,
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        UpdateGmoEpsilonOrderService $updateGmoEpsilonOrderService,
        SaveGmoEpsilonCreditCardExpirationService $saveGmoEpsilonCreditCardExpirationService,
        ChangeRegularStatusToRePaymentService $changeRegularStatusToRePaymentService,
        UpdateNextShippingDateFromRePaymentService $updateNextShippingDateFromRePaymentService,
        PointHelper $pointHelper
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->ipBlackListService = $ipBlackListService;
        $this->accessBlockProcessService = $accessBlockProcessService;
        $this->requestReceiveOrder3Service = $requestReceiveOrder3Service;
        $this->createSystemErrorResponseService = $createSystemErrorResponseService;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->saveGmoEpsilonCreditCardExpirationService = $saveGmoEpsilonCreditCardExpirationService;
        $this->changeRegularStatusToRePaymentService = $changeRegularStatusToRePaymentService;
        $this->updateNextShippingDateFromRePaymentService = $updateNextShippingDateFromRePaymentService;
        $this->pointHelper = $pointHelper;
    }

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
    public function verify(): PaymentResult
    {
        $PaymentResult = new PaymentResult();

        return $PaymentResult->setSuccess(true);
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(): PaymentResult
    {
        $request = Request::createFromGlobals();
        $PaymentResult = new PaymentResult();
        if (!$this->checkParameter($request, $this->getCheckParameter())) {
            logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().' 不正なGETリクエストを受信しました。');
            $PaymentResult->setErrors([
                '不正なリクエストを受信しました。',
            ]);

            return $PaymentResult;
        }

        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $this->Order,
                $request->get('trans_code'),
                $request->get('order_number')
            );

        $this->saveGmoEpsilonCreditCardExpirationService->handle();
        $this->changeRegularStatusToRePaymentService->handle($this->Order->getCustomer());
        $this->updateNextShippingDateFromRePaymentService->update($this->Order->getCustomer());
        if ($this->Order->getUsePoint() > 0) {
            // ユーザの保有ポイントを減算
            $this->pointHelper->prepare($this->Order, $this->Order->getUsePoint());
        }
        $PaymentResult->setSuccess(true);
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Reg Credit payment process end.');

        return $PaymentResult;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Reg Credit payment process start.');
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
        $dispatcher = new PaymentDispatcher();
        $results = $this
            ->requestReceiveOrder3Service
            ->handle(
                $this->Order,
                $this->eccubeConfig['gmo_epsilon']['st_code']['reg_credit']
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
