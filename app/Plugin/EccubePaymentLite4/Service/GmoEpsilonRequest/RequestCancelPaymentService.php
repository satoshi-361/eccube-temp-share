<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

/**
 * イプシロン決済サービスの取引の取消APIを利用するためのクラス
 */
class RequestCancelPaymentService
{
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    public function __construct(
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        PaymentStatusRepository $paymentStatusRepository
    ) {
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
    }

    /**
     * @return array
     */
    public function handle(Order $Order)
    {
        $status = 'NG';
        // gmo_epsilon_order_noのチェック
        if (is_null($Order->getGmoEpsilonOrderNo())) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' GMOイプシロンIDが未登録のため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
            ];
        }
        // 決済方法が
        // クレジットカード決済、Yahoo!ウォレット決済、PayPal決済、スマホキャリア決済、GMO後払い
        // 以外の場合はリクエストを行わない
        if ($Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['credit'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['reg_credit'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['ywallet'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['paypal'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['sphone'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['deferred']) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' '.$Order->getPaymentMethod().'はイプシロン決済サービスとのステータス同期を行いません',
                'status' => $status,
            ];
        }

        /** @var Config $Config */
        $Config = $this->configRepository->find(1);

        $response = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('cancel_payment'), [
                'contract_code' => $Config->getContractCode(),
                'order_number' => $Order->getGmoEpsilonOrderNo(),
            ]
        );
        $message = $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_DETAIL');
        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = 'キャンセル処理が完了しました。イプシロン決済システムの決済ステータスが「キャンセル」に変更されました。';
            $status = 'OK';
        }

        return [
            'result' => $result,
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
        ];
    }
}
