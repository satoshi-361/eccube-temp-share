<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Eccube\Common\EccubeConfig;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RequestGetUserInfoService
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

    public function handle(int $customerId): array
    {
        $status = 'NG';
        $responseXml = $this->gmoEpsilonRequestService->sendData(
            $this->gmoEpsilonUrlService->getUrl('get_user_info'), [
                'contract_code' => $this->config->getContractCode(),
                'user_id' => $customerId,
            ]
        );

        $message = $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'ERR_DETAIL');
        $result = (int) $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'RESULT');
        $cardExpire = '';
        $cardNumberMask = '';
        // 正常にカード番号が取得出来た場合はカード情報を返却する
        if ($result === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['ok']) {
            $message = '正常終了';
            $status = 'OK';
            $cardExpire = $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'CARD_EXPIRE');
            $cardExpire = mb_substr($cardExpire, 0, 4).'/'.mb_substr($cardExpire, 4, 2);
            $cardNumberMask = explode('-', $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'CARD_NUMBER_MASK'))[3];
        }

        return [
            'result' => (int) $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'RESULT'),
            'err_code' => (int) $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'ERR_CODE'),
            'message' => $message,
            'status' => $status,
            'cardBrand' => $this->gmoEpsilonRequestService->getXMLValue($responseXml, 'RESULT', 'CARD_BRAND'),
            'cardExpire' => $cardExpire,
            'cardNumberMask' => $cardNumberMask,
        ];
    }
}
