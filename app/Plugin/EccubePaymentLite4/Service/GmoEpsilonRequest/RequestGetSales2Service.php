<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RequestGetSales2Service
{
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        ConfigRepository $configRepository,
        SessionInterface $session,
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        EccubeConfig $eccubeConfig
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->config = $configRepository->get();
        $this->session = $session;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function handle($transCode = null, $orderNo = null)
    {
        $status = 'NG';
        if (empty($transCode) && empty($orderNo)) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'err_code' => 'trans_code、もしくはorder_numberいずれかのパラメータが必要です',
                'message' => '',
                'status' => $status,
                'order_number' => $orderNo,
                'payment_code' => '',
            ];
        }

        $arrParameter = [
            'contract_code' => $this->config->getContractCode(),
        ];
        if (!empty($transCode)) {
            $arrParameter['trans_code'] = $transCode;
        } else {
            $arrParameter['order_number'] = $orderNo;
        }

        $xmlResponse = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('getsales2'),
            $arrParameter
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_DETAIL');
        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT');
        if ($result === 0) {
            $message = '正常終了';
            $status = 'OK';
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
            'route' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'MEMO1'),
            'user_mail_add' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'USER_MAIL_ADD'),
            'conveni_limit' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'CONVENI_LIMIT'),
            'state' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'STATE'),
            'receipt_date' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RECEIPT_DATE'),
            'mission_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'MISSION_CODE'),
            'item_price' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ITEM_PRICE'),
            'receipt_no' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RECEIPT_NO'),
            'order_number' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ORDER_NUMBER'),
            'conveni_time' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'CONVENI_TIME'),
            'st_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ST_CODE'),
            'memo1' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'MEMO1'),
            'kigyou_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'KIGYOU_CODE'),
            'contract_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'CONTRACT_CODE'),
            'item_name' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ITEM_NAME'),
            'user_name' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'USER_NAME'),
            'paid' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'PAID'),
            'haraikomi_url' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'HARAIKOMI_URL'),
            'conveni_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'CONVENI_CODE'),
            'process_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'PROCESS_CODE'),
            'keitai' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'KEITAI'),
            'due_date' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'DUE_DATE'),
            'add_info' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ADD_INFO'),
            'user_id' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'USER_ID'),
            'memo2' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'MEMO2'),
            'trans_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'TRANS_CODE'),
        ];
    }
}
