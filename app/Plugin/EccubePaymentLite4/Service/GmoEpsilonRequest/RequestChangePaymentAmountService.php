<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class RequestChangePaymentAmountService
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

    public function __construct(
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
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
        if (is_null($Order->getCustomer())) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().'は、ゲスト購入の受注は金額変更処理に対応していないため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
            ];
        }
        if (!($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['credit'] ||
            $Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['reg_credit'] ||
            $Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['ywallet'])) {
            logs('gmo_epsilon')
                ->addInfo('受注ID: '.$Order->getId().' 決済種別'.$Order->getPaymentMethod().'は、金額変更に対応していないため、イプシロン決済サービスとの同期処理をスキップしました');

            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' 決済種別'.$Order->getPaymentMethod().'は、金額変更に対応していないため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
            ];
        }
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        /** @var OrderItem $OrderItem */
        $OrderItem = $Order->getOrderItems()->first();
        $productCode = $OrderItem->getProductCode();

        $xmlResponse = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('change_amount_payment'), [
                'contract_code' => $Config->getContractCode(),
                'mission_code' => 1, // TODO マジックナンバー
                'order_number' => $Order->getGmoEpsilonOrderNo(),
                'user_id' => $Order->getCustomer()->getId(),
                'item_code' => $productCode,
                'new_item_price' => $Order->getPaymentTotal(),
            ]
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_DETAIL');
        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '受注ID: '.$Order->getId().' イプシロン決済システムの金額が変更されました';
            $status = 'OK';
            logs('gmo_epsilon')
                ->addInfo('受注ID: '.$Order->getId().' イプシロン決済システムの金額が変更されました');
        }

        return [
            'result' => $result,
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
        ];
    }
}
