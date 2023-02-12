<?php

namespace Plugin\EccubePaymentLite4\Service\Method;

use DateTime;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\MailService;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PointHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\EccubePaymentLite4\Service\CreateSystemErrorResponseService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrder3Service;
use Plugin\EccubePaymentLite4\Service\UpdateGmoEpsilonOrderService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class Maillink implements PaymentMethodInterface
{
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
     * @var CreateSystemErrorResponseService
     */
    private $createSystemErrorResponseService;
    /**
     * @var UpdateGmoEpsilonOrderService
     */
    private $updateGmoEpsilonOrderService;
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
     * @var MailService
     */
    private $mailService;
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
        MailService $mailService,
        PointHelper $pointHelper
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->createSystemErrorResponseService = $createSystemErrorResponseService;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->requestReceiveOrder3Service = $requestReceiveOrder3Service;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->mailService = $mailService;
        $this->pointHelper = $pointHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Mail link payment process start.');
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());
        // if customer use point =>  rollback point of customer
        if ($this->Order->getUsePoint() > 0) {
            // 利用したポイントをユーザに戻す.
            $this->pointHelper->rollback($this->Order, $this->Order->getUsePoint());
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(): PaymentResult
    {
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $this->Order->setOrderStatus($OrderStatus);
        $this->Order->setPaymentDate(new DateTime());
        if ($this->Order->getUsePoint() > 0) {
            // ユーザの保有ポイントを減算
            $this->pointHelper->prepare($this->Order, $this->Order->getUsePoint());
        }
        $result = new PaymentResult();
        $result->setSuccess(true);
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Mail link payment process end.');

        return $result;
    }

    public function compProcess($paymentCode)
    {
        $request = Request::createFromGlobals();
        $arrPaymentMethod = array_flip($this->eccubeConfig['gmo_epsilon']['pay_id']);
        switch ($arrPaymentMethod[$paymentCode]) {
            case 'conveni':
            case 'payeasy':
                $OrderStatus = $this->orderStatusRepository->find(OrderStatus::IN_PROGRESS);
                break;
            default:
                $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
                break;
        }

        $this->Order->setOrderStatus($OrderStatus);
        $this->Order->setPaymentDate(new DateTime());

        // トランザクションコードを登録
        $this->Order->setTransCode($request->get('trans_code'));
        $this->Order->setGmoEpsilonOrderNo($request->get('order_number'));
        // メール送信
        $this->mailService->sendOrderMail($this->Order);

        logs('gmo_epsilon')->info('pay process end for maillink. order_id = '.$this->Order->getId());
    }

    public function getCheckParameter(): array
    {
        return [
            'contract_code',
            'trans_code',
            'order_number',
            'user_id',
            'state',
            'payment_code',
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
}
