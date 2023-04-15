<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Common\Constant;
use Eccube\Repository\PluginRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GetProductInformationFromOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonOrderNoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class RequestCreateRegularOrderService
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
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var GetProductInformationFromOrderService
     */
    private $getProductInformationFromOrderService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var GmoEpsilonOrderNoService
     */
    private $gmoEpsilonOrderNoService;
    /**
     * @var GetCardCgiUrlService
     */
    private $getCardCgiUrlService;
    /**
     * @var pluginRepository
     */
    private $pluginRepository;
    
    public function __construct(
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        ConfigRepository $configRepository,
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        EccubeConfig $eccubeConfig,
        GetProductInformationFromOrderService $getProductInformationFromOrderService,
        GmoEpsilonOrderNoService $gmoEpsilonOrderNoService,
        GetCardCgiUrlService $getCardCgiUrlService,
        PluginRepository $pluginRepository
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->eccubeConfig = $eccubeConfig;
        $this->getProductInformationFromOrderService = $getProductInformationFromOrderService;
        $this->configRepository = $configRepository;
        $this->gmoEpsilonOrderNoService = $gmoEpsilonOrderNoService;
        $this->getCardCgiUrlService = $getCardCgiUrlService;
        $this->pluginRepository = $pluginRepository;
    }

    public function handle(RegularOrder $RegularOrder, Order $Order, string $route)
    {
        $status = 'NG';
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $Customer = $RegularOrder->getCustomer();
        $gmoEpsilonOrderNo = $this->gmoEpsilonOrderNoService->create($Order->getId());
        $itemInformation = $this->getProductInformationFromOrderService->handle($Order);
        $PluginVersion = $this->pluginRepository->findByCode('EccubePaymentLite4')->getVersion();
        
        
        $xmlResponse = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('receive_order3'), [
                'version' => 1,
                'contract_code' => $Config->getContractCode(),
                'user_id' => $Customer->getId(),
                'user_name' => $Customer->getName01().' '.$Customer->getName02(),
                'user_mail_add' => $Customer->getEmail(),
                'item_code' => $itemInformation['item_code'],
                'item_name' => $itemInformation['item_name'],
                'order_number' => $gmoEpsilonOrderNo,
                'st_code' => '11000-0000-00000-00000-00000-00000-00000',
                'mission_code' => 1,
                'item_price' => (int) $Order->getPaymentTotal(),
                'process_code' => 2, // 登録済み課金
                'memo1' => $route, // 管理画面より登録したことを記録。card3.cgiのリダイレクト先(eccube_payment_lite4_payment_complete)で利用する。
                'memo2' => 'EC-CUBE_' . Constant::VERSION . '_' . $PluginVersion . "_" . date('YmdHis'),
                'xml' => 1,
            ]
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_DETAIL');
        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT');
        $redirectUrl = '';
        // 正常にカード番号が取得出来た場合はカード情報を返却する
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '正常終了';
            $status = 'OK';
            $redirectUrl = $this->getCardCgiUrlService->getUrl($xmlResponse);
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
            'redirectUrl' => $redirectUrl,
            'order_no' => $gmoEpsilonOrderNo,
        ];
    }
}
