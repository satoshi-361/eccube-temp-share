<?php

namespace Plugin\EccubePaymentLite4\Service;

use DateTime;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class IsResumeRegularOrder
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
     * 定期受注が再開可能の場合はtrue
     *
     * @return bool
     */
    public function handle(RegularOrder $RegularOrder)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $regularResumbablePeriod = $Config->getRegularResumablePeriod();
        // 定期再開可能期間が設定されていない場合は、無制限で再開可能
        if (is_null($regularResumbablePeriod)) {
            return true;
        }
        // 定期ステータスが「休止」以外の場合は処理を行わない。
        if ($RegularOrder->getRegularStatus()->getId() !== RegularStatus::SUSPEND) {
            return true;
        }
        /** @var DateTime $regularStopDate */
        $regularStopDate = $RegularOrder->getRegularStopDate();
        if (is_null($regularStopDate)) {
            return true;
        }
        $today = new DateTime('today');
        $regularResumptionDeadLine = $regularStopDate->modify('+ '.$regularResumbablePeriod.'day 00:00:00');
        // 定期再開可能期間［regular_resumable_period］＋休止日［regular_stop_date］）＜今日　の場合
        if ($regularResumptionDeadLine < $today) {
            return false;
        }

        return true;
    }
}
