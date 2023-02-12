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
 * イプシロン決済サービスの取引の実売上APIを利用するためのクラス
 */
class RequestSalesPaymentService
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

    public function handle(Order $Order)
    {
        $status = 'NG';
        // gmo_epsilon_order_noのチェック
        if (is_null($Order->getGmoEpsilonOrderNo())) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'message' => '受注ID: '.$Order->getId().' GMOイプシロンIDが未登録のため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
                'route' => '',
            ];
        }

        // 実売上処理を行う決済方法は
        // クレジットカード決済、登録済みのクレジットカード決済、Yahoo!ウォレット決済、スマホキャリア決済、paypay
        if ($Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['credit'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['reg_credit'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['ywallet'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['sphone'] &&
            $Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['paypay']) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' '.$Order->getPaymentMethod().'はイプシロン決済サービスとのステータス同期を行いません',
                'status' => $status,
                'route' => '',
            ];
        }

        /** @var Config $Config */
        $Config = $this->configRepository->find(1);

        $response = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('sales_payment'), [
                'contract_code' => $Config->getContractCode(),
                'order_number' => $Order->getGmoEpsilonOrderNo(),
            ]
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_DETAIL');

        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '受注ID: '.$Order->getId().' 実売上処理が完了しました。イプシロン決済システムの決済ステータスが「仮売上」→ 「課金済み」に変更されました。';
            $status = 'OK';
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
            'route' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'MEMO1'),
        ];
    }
}
