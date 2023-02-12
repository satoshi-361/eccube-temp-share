<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class RequestShippingRegistrationService
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
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' GMOイプシロンIDが未登録のため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
            ];
        }

        // 決済種別のチェック
        // GMO後払いではない場合処理をスキップする
        if ($Order->getPaymentMethod() !== $this->eccubeConfig['gmo_epsilon']['pay_name']['deferred']) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' 決済種別がGMO後払いではないため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
            ];
        }

        /** @var Config $Config */
        $Config = $this->configRepository->find(1);

        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();

        // trackingNumberのチェック
        if (is_null($Shipping->getTrackingNumber())) {
            return [
                'result' => $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ng'],
                'err_code' => '',
                'message' => '受注ID: '.$Order->getId().' お問い合わせ番号が未登録のため、イプシロン決済サービスとの同期処理をスキップしました',
                'status' => $status,
            ];
        }
        $response = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('ship_request'), [
                'contract_code' => $Config->getContractCode(),
                'order_number' => $Order->getGmoEpsilonOrderNo(),
                'delivery_com_code' => $Shipping->getDelivery()->getDeliveryCompany()->getId(),
                'ship_no' => $Shipping->getTrackingNumber(),
            ]
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_DETAIL');

        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT');
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '受注ID: '.$Order->getId().' 出荷登録処理が完了しました。イプシロン決済システムの決済ステータスが「仮売上」→ 「出荷登録中」に変更されました。';
            $status = 'OK';
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($response, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
        ];
    }
}
