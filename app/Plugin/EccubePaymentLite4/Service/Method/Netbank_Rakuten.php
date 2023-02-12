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
use Plugin\EccubePaymentLite4\Service\CreateSystemErrorResponseService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrder3Service;
use Plugin\EccubePaymentLite4\Service\UpdateGmoEpsilonOrderService;
use Plugin\EccubePaymentLite4\Service\UpdatePaymentStatusService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Netbank_Rakuten implements PaymentMethodInterface
{
    /**
     * @var Order
     */
    protected $Order;
    /**
     * @var FormInterface
     */
    protected $form;

    private $eccubeConfig;
    /**
     * @var RequestReceiveOrder3Service
     */
    private $requestReceiveOrder3Service;
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
     * @var CreateSystemErrorResponseService
     */
    private $createSystemErrorResponseService;
    /**
     * @var UpdatePaymentStatusService
     */
    private $updatePaymentStatusService;
    /**
     * @var PointHelper
     */
    protected $pointHelper;

    public function __construct(
        EccubeConfig $eccubeConfig,
        CreateSystemErrorResponseService $createSystemErrorResponseService,
        UpdateGmoEpsilonOrderService $updateGmoEpsilonOrderService,
        RequestReceiveOrder3Service $requestReceiveOrder3Service,
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        UpdatePaymentStatusService $updatePaymentStatusService,
        PointHelper $pointHelper
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->createSystemErrorResponseService = $createSystemErrorResponseService;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->requestReceiveOrder3Service = $requestReceiveOrder3Service;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->updatePaymentStatusService = $updatePaymentStatusService;
        $this->pointHelper = $pointHelper;
    }

    public function getCheckParameter(): array
    {
        return [
            'trans_code',
            'order_number',
            'user_id',
            'payment_code',
            'state',
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
        $result = new PaymentResult();
        if (!$this->checkParameter($request, $this->getCheckParameter())) {
            logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().' 不正なGETリクエストを受信しました。');
            $result->setErrors([
                '不正なリクエストを受信しました。',
            ]);

            return $result;
        }

        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $this->Order,
                $request->get('trans_code'),
                $request->get('order_number')
            );
        $this->updatePaymentStatusService->handle(
            $this->Order,
            $request->query->get('state')
        );
        if ($this->Order->getUsePoint() > 0) {
            // ユーザの保有ポイントを減算
            $this->pointHelper->prepare($this->Order, $this->Order->getUsePoint());
        }
        $result->setSuccess(true);
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Rakuten Netbank payment process end.');

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Rakuten Netbank payment process start.');
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
                $this->eccubeConfig['gmo_epsilon']['st_code']['netbank_rakuten']
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
