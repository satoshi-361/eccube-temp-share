<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GetProductInformationFromOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonOrderNoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class RequestReceiveOrderService
{
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var object|null
     */
    private $Config;
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var GetProductInformationFromOrderService
     */
    private $getProductInformationFromOrderService;
    /**
     * @var GmoEpsilonOrderNoService
     */
    private $gmoEpsilonOrderNoService;

    public function __construct(
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        ConfigRepository $configRepository,
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        EccubeConfig $eccubeConfig,
        GetProductInformationFromOrderService $getProductInformationFromOrderService,
        GmoEpsilonOrderNoService $gmoEpsilonOrderNoService
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->Config = $configRepository->get();
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->eccubeConfig = $eccubeConfig;
        $this->getProductInformationFromOrderService = $getProductInformationFromOrderService;
        $this->gmoEpsilonOrderNoService = $gmoEpsilonOrderNoService;
    }

    public function handle($Customer, $processCode, $route, $Order = null)
    {
        $status = 'NG';
        $parameters = [
            'contract_code' => $this->Config->getContractCode(),
            'user_id' => $Customer->getId(),
            'user_name' => $Customer->getName01().' '.$Customer->getName02(),
            'user_mail_add' => $Customer->getEmail(),
            'st_code' => '11000-0000-00000-00000-00000-00000-00000',
            'process_code' => $processCode,
            'memo1' => $route,
            'memo2' => 'EC-CUBE4_'.date('YmdHis'),
            'xml' => 1,
            'version' => 1,
        ];
        if ($processCode === 2) {
            $gmoEpsilonOrderNo = $this->gmoEpsilonOrderNoService->create($Order->getId());
            $itemInformation = $this->getProductInformationFromOrderService->handle($Order);
            $parameters['item_code'] = $itemInformation['item_code'];
            $parameters['item_name'] = $itemInformation['item_name'];
            $parameters['order_number'] = $gmoEpsilonOrderNo;
            $parameters['mission_code'] = 1;
            $parameters['item_price'] = (int) $Order->getPaymentTotal();
        }

        $response = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl(
                'receive_order3'),
            $parameters
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_DETAIL');

        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '正常終了';
            $status = 'OK';
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
            'url' => $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'REDIRECT'),
        ];
    }
}
