<?php

namespace Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest;

use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;

class GetCardCgiUrlService
{
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;

    public function __construct(
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        GmoEpsilonUrlService $gmoEpsilonUrlService
    ) {
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
    }

    public function getUrl(array $xmlResponse): string
    {
        return $this->gmoEpsilonRequestService->getXMLValue($xmlResponse, 'RESULT', 'REDIRECT');
    }
}
