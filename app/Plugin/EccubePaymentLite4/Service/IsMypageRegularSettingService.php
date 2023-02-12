<?php

namespace Plugin\EccubePaymentLite4\Service;

use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class IsMypageRegularSettingService
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

    public function handle(int $id): bool
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        /** @var MyPageRegularSetting[] $MypageRegularSettings */
        $MypageRegularSettings = $Config->getMypageRegularSettings()->toArray();
        foreach ($MypageRegularSettings as $MyPageRegularSetting) {
            if ($MyPageRegularSetting->getId() === $id) {
                return true;
            }
        }

        return false;
    }
}
