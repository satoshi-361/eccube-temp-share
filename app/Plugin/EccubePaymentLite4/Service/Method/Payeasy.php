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
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetSales2Service;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrder3Service;
use Plugin\EccubePaymentLite4\Service\UpdateGmoEpsilonOrderService;
use Plugin\EccubePaymentLite4\Service\UpdatePaymentStatusService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Payeasy implements PaymentMethodInterface
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
     * @var RequestGetSales2Service
     */
    private $requestGetSales2Service;
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
        RequestGetSales2Service $requestGetSales2Service,
        UpdatePaymentStatusService $updatePaymentStatusService,
        PointHelper $pointHelper
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->createSystemErrorResponseService = $createSystemErrorResponseService;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->requestReceiveOrder3Service = $requestReceiveOrder3Service;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->requestGetSales2Service = $requestGetSales2Service;
        $this->updatePaymentStatusService = $updatePaymentStatusService;
        $this->pointHelper = $pointHelper;
    }

    /**
     * @param $results
     * @param $result
     */
    public function getPaymentInfo($results, $result): string
    {
        $kigyou_code = $results['kigyou_code'];
        $receipt_no = $results['receipt_no'];
        $conveni_limit = $results['conveni_limit'];

        // エラーチェック
        if (empty($receipt_no) || empty($kigyou_code) && empty($conveni_limit)) {
            logs('gmo_epsilon')->error('request error payeasy');
            $result->setErrors([
                'ペイジー決済情報の取得に失敗しました。この注文についてショップにお問合せください。',
            ]);
        }

        // ペイジー決済の決済情報を設定
        $pay_info = "収納機関番号：$kigyou_code\n";
        $pay_info .= "確認番号：$receipt_no\n";
        $pay_info .= "支払期限：$conveni_limit\n";

        return $pay_info;
    }

    /**
     * 決済情報を受注完了メッセージにセット
     *
     * @param string pay_info
     */
    public function setOrderCompleteMessages($pay_info)
    {
        $complete_mail_message = <<<EOT
        ************************************************
        　ペイジー決済情報
        ************************************************
        $pay_info
EOT;

        $pay_info = nl2br($pay_info, false);

        $complete_message = <<<EOT
        <div class="ec-rectHeading">
            <h2>■ペイジー決済情報</h2>
        </div>
        <p style="text-align:left; word-wrap: break-word; white-space: normal;">$pay_info</p>
EOT;

        // 注文完了メールにメッセージを追加
        $this->Order->appendCompleteMailMessage($complete_mail_message);
        // 注文完了画面にメッセージを追加
        $this->Order->appendCompleteMessage($complete_message);
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
        $results = $this
            ->requestGetSales2Service
            ->handle(
                $request->query->get('trans_code'),
                $request->query->get('order_number')
            );
        if ($results['status'] === 'NG') {
            $PaymentResult->setErrors([
                'ペイジー決済情報の取得に失敗しました。この注文についてショップにお問合せください。',
            ]);

            return $PaymentResult;
        }
        $this->setOrderCompleteMessages(
            $this->getPaymentInfo($results, $PaymentResult)
        );
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
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Payeasy payment process end.');

        return $PaymentResult->setSuccess(true);
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Payeasy payment process start.');
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
                $this->eccubeConfig['gmo_epsilon']['st_code']['payeasy']
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
