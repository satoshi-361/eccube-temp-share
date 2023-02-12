<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Common\EccubeConfig;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class GmoEpsilonUrlService
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
    }

    /**
     * @return mixed
     */
    public function getUrl(string $connectTo)
    {
        return $this->eccubeConfig['gmo_epsilon']['url'][$this->getEnv()][$connectTo];
    }

    private function getGmoEpsilonConfig()
    {
        return $this->configRepository->find(1);
    }

    private function getEnv(): string
    {
        /** @var Config $config */
        $config = $this->getGmoEpsilonConfig();
        $environment = $config->getEnvironmentalSetting();
        if ($environment === Config::ENVIRONMENTAL_SETTING_PRODUCTION) {
            return 'prod';
        }

        return 'dev';
    }
}
