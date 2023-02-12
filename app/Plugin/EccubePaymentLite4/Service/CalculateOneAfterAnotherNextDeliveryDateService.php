<?php

namespace Plugin\EccubePaymentLite4\Service;

use DateTime;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;

class CalculateOneAfterAnotherNextDeliveryDateService
{
    /**
     * @var GetRegularSpecificWeekCycle
     */
    private $getRegularSpecificWeekCycle;

    public function __construct(
        GetRegularSpecificWeekCycle $getRegularSpecificWeekCycle
    ) {
        $this->getRegularSpecificWeekCycle = $getRegularSpecificWeekCycle;
    }

    /**
     * 次次回のお届け予定日を計算する
     *
     * @return DateTime
     */
    public function calc(RegularCycle $RegularCycle, DateTime $nextDeliveryDate)
    {
        $DateTime = clone $nextDeliveryDate;
        $regularCycleDay = $RegularCycle->getDay();
        /** @var RegularCycleType $RegularCycleType */
        $RegularCycleType = $RegularCycle->getRegularCycleType();
        if ($RegularCycleType->getId() === RegularCycleType::REGULAR_SPECIFIC_WEEK_CYCLE) {
            return $this->getRegularSpecificWeekCycle->handle($RegularCycle, clone $DateTime);
        }
        if ($RegularCycleType->getId() === RegularCycleType::REGULAR_DAILY_CYCLE) {
            $DateTime->modify('+'.$regularCycleDay.' day');
        } elseif ($RegularCycleType->getId() === RegularCycleType::REGULAR_WEEKLY_CYCLE) {
            $DateTime->modify('+'.$regularCycleDay.' week');
        } elseif ($RegularCycleType->getId() === RegularCycleType::REGULAR_MONTHLY_CYCLE) {
            $DateTime->modify('+'.$regularCycleDay.' month');
        } elseif ($RegularCycleType->getId() === RegularCycleType::REGULAR_SPECIFIC_DAY_CYCLE) {
            return $this->getNextMonth(clone $DateTime, $regularCycleDay);
        }

        return $DateTime;
    }

    private function getNextMonth($DateTime, $regularCycleDay): \DateTime
    {
        $nextMonthDate = clone $DateTime;
        $nextMonthDate->modify('last day of next month');
        $year = $nextMonthDate->format('Y');
        $month = $nextMonthDate->format('m');
        // 存在しない日付の場合は、日付を月末とする
        if (!checkdate($month, $regularCycleDay, $year)) {
            return $nextMonthDate;
        }
        // 存在する日付の場合
        return new \DateTime($year.'-'.$month.'-'.$regularCycleDay);
    }
}
