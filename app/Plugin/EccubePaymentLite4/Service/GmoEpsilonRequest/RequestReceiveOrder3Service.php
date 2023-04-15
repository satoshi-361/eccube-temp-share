<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Common\Constant;
use Eccube\Repository\PluginRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GetProductInformationFromOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonOrderNoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class RequestReceiveOrder3Service
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
     * @var GmoEpsilonOrderNoService
     */
    private $gmoEpsilonOrderNoService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

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
        PluginRepository $pluginRepository
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->configRepository = $configRepository;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->eccubeConfig = $eccubeConfig;
        $this->getProductInformationFromOrderService = $getProductInformationFromOrderService;
        $this->gmoEpsilonOrderNoService = $gmoEpsilonOrderNoService;
        $this->pluginRepository = $pluginRepository;
    }

    /**
     * @return array
     */
    public function handle(Order $Order, $stCode, $conveniCode = null)
    {
        $status = 'NG';
        /** @var Config $Config */
        $arrParameter = $this->setParameter($Order, $stCode, $conveniCode);
        $xmlResponse = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('receive_order3'),
            $arrParameter
        );
        $message = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_DETAIL');
        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '正常終了';
            $status = 'OK';
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
            'url' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'REDIRECT'),
            'trans_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'TRANS_CODE'),
            'order_number' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ORDER_NUMBER'),
            'state' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'STATE'),
            'conveni_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'CONVENI_CODE'),
            'receipt_no' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RECEIPT_NO'),
            'haraikomi_url' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'HARAIKOMI_URL'),
            'kigyou_code' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'KIGYOU_CODE'),
            'conveni_limit' => $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'CONVENI_LIMIT'),
        ];
    }

    /**
     * リクエストパラメータを設定
     *
     * @param Order $Order
     *
     * @return array
     */
    private function setParameter($Order, $stCode, $conveniCode)
    {
        $Customer = $Order->getCustomer();
        // ゲスト購入の場合は、ユーザーIDをnon_customerとする
        $user_id = is_null($Customer) ? 'non_customer' : $Customer->getId();

        $itemInfo = $this->getProductInformationFromOrderService->handle($Order);

        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        
        $PluginVersion = $this->pluginRepository->findByCode('EccubePaymentLite4')->getVersion();
        
        // 送信データを作成
        $parameter = [
            'contract_code' => $Config->getContractCode(),
            'user_id' => $user_id,                                              // ユーザID
            'user_name' => $Order->getName01().$Order->getName02(),             // ユーザ名
            'user_mail_add' => $Order->getEmail(),                              // メールアドレス
            'order_number' => $this->gmoEpsilonOrderNoService->create($Order->getId()),   // オーダー番号
            'item_code' => $itemInfo['item_code'],                              // 商品コード(代表)
            'item_name' => $itemInfo['item_name'],                              // 商品名(代表)
            'item_price' => $Order->getPaymentTotal(),                          // 商品価格(税込み総額)
            'st_code' => $stCode,                                        // 決済区分
            'mission_code' => 1,                              // 課金区分(固定)
            'process_code' => 1,                              // 処理区分(固定)
            'xml' => 1,                                                       // 応答形式(固定)
            'memo1' => '',                                                      // 予備01
            'memo2' => 'EC-CUBE_' . Constant::VERSION . '_' . $PluginVersion . "_" . date('YmdHis'),  // 予備02
            'version' => 1,
        ];
        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['reg_credit']) {
            // 非会員かつ定期商品の場合、空文字に置き換え ※ エラーとする
            if ($parameter['user_id'] === 'non_customer' &&
                $Order->getProductOrderItems()[0]->getProductClass()->getSaleType()->getName() === '定期商品') {
                $parameter['user_id'] = '';
            }
            $parameter['process_code'] = 2;
        }
        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['virtual_account'] ||
            $Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['webmoney']) {
            $parameter['version'] = 2;
        }
        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['conveni']) {
            $parameter['conveni_code'] = $conveniCode;
            $parameter['user_tel'] = $Order->getPhoneNumber();
            $parameter['haraikomi_mail'] = 0;
        }

        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['deferred']) {
            $Shipping = $Order->getShippings()[0];
            $parameter['delivery_code'] = '99';
            $parameter['consignee_postal'] = $Shipping->getPostalCode();
            $parameter['consignee_name'] = $Shipping->getName01().$Shipping->getName02();
            $parameter['consignee_address'] = $Shipping->getPref().$Shipping->getAddr01().$Shipping->getAddr02();
            $parameter['consignee_tel'] = $Shipping->getPhonenumber();
            $parameter['orderer_postal'] = $Order->getPostalCode();
            $parameter['orderer_name'] = $Order->getName01().$Order->getName02();
            $parameter['orderer_address'] = $Order->getPref().$Order->getAddr01().$Order->getAddr02();
            $parameter['orderer_tel'] = $Order->getPhonenumber();
        }

        // Start specific params of 3ds 2.0 (hard code)
        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['credit']
            || $Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['reg_credit']) {
            $Shipping = $Order->getShippings()[0];
            $parameter['tds_flag'] = 21; // 3DS2.0フラ グ
            $parameter['billAddrCity'] = $Order->getPref()->getName(); // 請求先住所(都市)
            $parameter['billAddrCountry'] = 392; // 請求先住所(国番号) =>  set default is Japan
            $parameter['billAddrLine1'] = $Order->getAddr01(); // 請求先住所(区域部分_1行目)
            $parameter['billAddrLine2'] = $Order->getAddr02(); // 請求先住所(区域部分_2行目)
            $parameter['billAddrLine3'] = ''; // 請求先住所(区域部分_3行目)
            $parameter['billAddrPostCode'] = $Order->getPostalCode(); // 請求先住所(郵便番号)
            $parameter['billAddrState'] = $Order->getPref()->getId(); // 請求先住所 (州または都道府県番号)
            $parameter['shipAddrCity'] = $Shipping->getPref()->getName(); // 送り先住所(都市)
            $parameter['shipAddrCountry'] = 392; // 送り先住所(国番号) => set default is Japan
            $parameter['shipAddrLine1'] = $Shipping->getAddr01(); // 送り先住所(区域部分_1行目)
            $parameter['shipAddrLine2'] = $Shipping->getAddr02(); // 送り先住所(区域部分_2行目)
            $parameter['shipAddrLine3'] = ''; // 送り先住所(区域部分_3行目)
            $parameter['shipAddrPostCode'] = $Shipping->getPostalCode(); // 送り先住所(郵便番号)
            $parameter['shipAddrState'] = $Shipping->getPref()->getId(); // 送り先住所 (州または都道府県番号)
            $parameter['threeDSReqAuthMethod'] = '02'; // ログイ ン認証方法
            $parameter['challengeInd'] = '04'; // チャレンジ要求
        }
        // End specific params of 3ds 2.0

        // write log for sent parameters to Epsilon
        logs('gmo_epsilon')->info('Parameter sent 3DS 2.0 => リンク決済 (RequestDirectCardPaymentService):  = '.print_r($parameter, true));
        return $parameter;
    }
}
