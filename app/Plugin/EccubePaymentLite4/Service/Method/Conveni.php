<?php

namespace Plugin\EccubePaymentLite4\Service\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
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

class Conveni implements PaymentMethodInterface
{
    /**
     * @var UpdatePaymentStatusService
     */
    private $updatePaymentStatusService;
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
     * @var PointHelper
     */
    protected $pointHelper;

    public function __construct(
        RequestReceiveOrder3Service $requestReceiveOrder3Service,
        EccubeConfig $eccubeConfig,
        OrderStatusRepository $orderStatusRepository,
        UpdatePaymentStatusService $updatePaymentStatusService,
        CreateSystemErrorResponseService $createSystemErrorResponseService,
        UpdateGmoEpsilonOrderService $updateGmoEpsilonOrderService,
        PurchaseFlow $shoppingPurchaseFlow,
        PointHelper $pointHelper
    ) {
        $this->requestReceiveOrder3Service = $requestReceiveOrder3Service;
        $this->eccubeConfig = $eccubeConfig;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->updatePaymentStatusService = $updatePaymentStatusService;
        $this->createSystemErrorResponseService = $createSystemErrorResponseService;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->pointHelper = $pointHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Conveni payment process start.');
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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(): PaymentResult
    {
        logs('gmo_epsilon')->info('受注ID: '.$this->Order->getId().'Conveni payment process end.');
        $PaymentResult = new PaymentResult();
        $results = $this
            ->requestReceiveOrder3Service
            ->handle(
                $this->Order,
                $this->eccubeConfig['gmo_epsilon']['st_code']['conveni'],
                $this->form['convenience']->getData()->getConveniCode()
            );
        if ($results['status'] === 'NG') {
            logs('gmo_epsilon')->error('ERR_CODE = '.$results['err_code']);
            logs('gmo_epsilon')->error('ERR_DETAIL = '.$results['message']);
            $PaymentResult->setErrors([
                'コンビニ決済情報の登録に失敗しました。この注文についてショップにお問合せください。',
            ]);

            return $PaymentResult;
        }

        $pay_info = $this->getPaymentInfo($results);
        $this->setOrderCompleteMessages($pay_info);
        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $this->Order,
                $results['trans_code'],
                $results['order_number']
            );
        $this->updatePaymentStatusService->handle(
            $this->Order,
            $results['state']
        );
        if ($this->Order->getUsePoint() > 0) {
            // ユーザの保有ポイントを減算
            $this->pointHelper->prepare($this->Order, $this->Order->getUsePoint());
        }
        $PaymentResult->setSuccess(true);

        return $PaymentResult;
    }

    /**
     * @param $result
     */
    private function getPaymentInfo($result): string
    {
        // リクエスト結果を取得
        $receipt_no = $result['receipt_no'];
        $haraikomi_url = $result['haraikomi_url'];
        $kigyou_code = $result['kigyou_code'];
        $conveni_limit = $result['conveni_limit'];
        $tel = $this->Order->getPhoneNumber();

        // コンビニ別に決済情報を設定
        $pay_info = '';
        switch ($result['conveni_code']) {
            case $this->eccubeConfig['gmo_epsilon']['conveni_id']['seveneleven']:
                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_pre_message']['seveneleven']."\n\n";

                $pay_info .= "払込票URL:$haraikomi_url\n";
                $pay_info .= "払込票番号（13桁）:$receipt_no\n";
                $pay_info .= "お支払期限:$conveni_limit\n\n";

                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_message']['seveneleven']."\n\n";
                break;
            case $this->eccubeConfig['gmo_epsilon']['conveni_id']['familymart']:
                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_pre_message']['familymart']."\n\n";

                $pay_info .= "企業コード（5桁）:$kigyou_code\n";
                $pay_info .= "注文番号（12桁）:$receipt_no\n";
                $pay_info .= "お支払期限:$conveni_limit\n\n";

                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_message']['familymart']."\n\n";
                break;
            case $this->eccubeConfig['gmo_epsilon']['conveni_id']['lawson']:
                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_pre_message']['lawson']."\n\n";

                $pay_info .= "受付番号（6桁）:$receipt_no\n";
                $pay_info .= "電話番号:$tel\n";
                $pay_info .= "お支払期限:$conveni_limit\n\n";

                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_message']['lawson']."\n\n";
                break;
            case $this->eccubeConfig['gmo_epsilon']['conveni_id']['seicomart']:
                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_pre_message']['seicomart']."\n\n";

                $pay_info .= "受付番号（6桁）:$receipt_no\n";
                $pay_info .= "電話番号:$tel\n";
                $pay_info .= "お支払期限:$conveni_limit\n\n";

                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_message']['seicomart']."\n\n";
                break;
            case $this->eccubeConfig['gmo_epsilon']['conveni_id']['ministop']:
                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_pre_message']['ministop']."\n\n";

                $pay_info .= "払込票番号:$receipt_no\n";
                $pay_info .= "電話番号:$tel\n";
                $pay_info .= "お支払期限:$conveni_limit\n\n";

                $pay_info .= $this->eccubeConfig['gmo_epsilon']['conveni_message']['ministop']."\n\n";
                break;
        }

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
コンビニ決済情報
************************************************
$pay_info
EOT;

        // URLをリンクに変換
        $pay_info = preg_replace('/(http.*?)\\n/', "<a href='#' onClick=\"window.open('$1'); return false;\" >$1</a>\n", $pay_info);
        $pay_info = nl2br($pay_info, false);

        $complete_message = <<<EOT
        <div class="ec-rectHeading">
            <h2>■コンビニ決済情報</h2>
        </div>
        <p style="text-align:left; word-wrap: break-word; white-space: normal;">$pay_info</p>
EOT;

        // 注文完了メールにメッセージを追加
        $this->Order->appendCompleteMailMessage($complete_mail_message);
        // 注文完了画面にメッセージを追加
        $this->Order->appendCompleteMessage($complete_message);
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
