<?php

namespace Plugin\EccubePaymentLite4\Service;

use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class IsActiveRegularService
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);

        return $Config->getRegular();
    }
}
