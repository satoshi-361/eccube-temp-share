<?php

namespace Plugin\EccubePaymentLite4\Service;

use DateTime;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;

class CalculateNextDeliveryDateService
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

    // 指定した日にちと、選択された定期サイクルから、次回お届け予定日を計算する。
    public function calc(RegularCycle $RegularCycle, int $dateToAdd)
    {
        /** @var RegularCycleType $RegularCycleType */
        $RegularCycleType = $RegularCycle->getRegularCycleType();
        $DateTime = new \DateTime('today');

        $DateTime->modify('+'.$dateToAdd.' days');
        if ($RegularCycleType->getId() === RegularCycleType::REGULAR_SPECIFIC_WEEK_CYCLE) {
            return $this->getRegularSpecificWeekCycle->handle($RegularCycle, clone $DateTime);
        }
        $regularCycleDay = $RegularCycle->getDay();

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

    // 指定した日にちと、選択された定期サイクルから、次回お届け予定日を計算する。
    public function calc_delivery(RegularCycle $RegularCycle,  DateTime $nextDeliveryDate)
    {
        /** @var RegularCycleType $RegularCycleType */
        $RegularCycleType = $RegularCycle->getRegularCycleType();;
        $DateTime = $nextDeliveryDate;

        if ($RegularCycleType->getId() === RegularCycleType::REGULAR_SPECIFIC_WEEK_CYCLE) {
            return $this->getRegularSpecificWeekCycle->handle($RegularCycle, clone $DateTime);
        }
        $regularCycleDay = $RegularCycle->getDay();

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
}
