<?php

namespace Plugin\EccubePaymentLite4\Service;

use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class NextDeliveryChangeableRangeService
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

    public function get(RegularShipping $RegularShipping)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $deadlineDate = new \DateTime('today');
        $deadlineDate->modify('+'.$Config->getRegularOrderDeadline().' day');
        $day = $Config->getNextDeliveryDateChangeableRangeDays();
        $format = 'Y/m/d';
        $todayForTheNextDay = clone $RegularShipping->getNextDeliveryDate();
        $todayForThePreviousDay = clone $RegularShipping->getNextDeliveryDate();
        $changeableRangeDays = [
            $todayForTheNextDay->format($format) => clone $todayForTheNextDay,
        ];
        for ($i = 1; $i <= $day; $i++) {
            $nextDay = $todayForTheNextDay->modify('+1 day');
            $changeableRangeDays[$nextDay->format($format)] = clone $nextDay;
            // 「次回お届け予定日」の前方への日付追加について、
            // 「定期受注注文締切日」に実行される前提の定期受注作成バッチが実行されるようにするため
            // 「定期受注注文締切日」を含む過去の日付を設定できないようにする。
            $previousDay = $todayForThePreviousDay->modify('-1 day');
            if ($deadlineDate < $previousDay) {
                $changeableRangeDays[$previousDay->format($format)] = clone $previousDay;
            }
        }
        asort($changeableRangeDays);

        return $changeableRangeDays;
    }
}
