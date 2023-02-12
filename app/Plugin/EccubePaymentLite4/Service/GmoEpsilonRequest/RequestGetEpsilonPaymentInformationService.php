<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class RequestGetEpsilonPaymentInformationService
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    public function __construct(
        ConfigRepository $configRepository,
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        EccubeConfig $eccubeConfig
    ) {
        $this->configRepository = $configRepository;
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->eccubeConfig = $eccubeConfig;
    }

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
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $response = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('getsales2'),
            [
                'contract_code' => $Config->getContractCode(),
                'order_number' => $Order->getGmoEpsilonOrderNo(),
            ]
        );
        $message = $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_DETAIL');

        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $status = 'OK';
        }
        // 決済が未登録の場合
        if (count($response) === 1) {
            // TODO
            return [
                'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT'),
                'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_CODE'),
                'message' => $message,
                'status' => $status,
            ];
        }

        // 決済登録済みの場合
        return [
            'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok'],
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_CODE'),
            'message' => '受注ID: '.$Order->getId().' 決済情報取得完了',
            'status' => $status,
            'contract_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'CONTRACT_CODE'),
            'email' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'USER_MAIL_ADD'),
            'state' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'STATE'),
            'trans_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'TRANS_CODE'),
            'mission_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'MISSION_CODE'),
            'process_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'PROCESS_CODE'),
            'payment_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'PAYMENT_CODE'),
            'order_number' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ORDER_NUMBER'),
            'item_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ITEM_CODE'),
            'item_name' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ITEM_NAME'),
            'item_price' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ITEM_PRICE'),
            'memo1' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'MEMO1'),
            'memo2' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'MEMO2'),
            'customer_id' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'USER_ID'),
            'user_name' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'USER_NAME'),
            'pay_time' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'PAY_TIME'),
            'keitai' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'KEITAI'),
            'credit_flag' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'CREDIT_FLAG'),
            'credit_time' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'CREDIT_TIME'),
            'due_date' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'DUE_DATE'),
            'card_st_code' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'CARD_ST_CODE'),
            'add_info' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ADD_INFO'),
            'update_date' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'LAST_UPDATE'),
        ];
    }
}
