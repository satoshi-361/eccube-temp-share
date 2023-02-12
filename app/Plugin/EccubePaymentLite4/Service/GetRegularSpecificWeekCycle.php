<?php

namespace Plugin\EccubePaymentLite4\Service;

use Plugin\EccubePaymentLite4\Entity\RegularCycle;

class GetRegularSpecificWeekCycle
{
    public function handle($RegularCycle, $DateTime)
    {
        $weekNumber = (int) $DateTime->format('w') + 1;
        if ($RegularCycle->getWeek() === RegularCycle::SUNDAY) {
            if ($weekNumber !== RegularCycle::SUNDAY) {
                $DateTime->modify('next Sunday');
            }

            return $DateTime->modify('next Sunday');
        } elseif ($RegularCycle->getWeek() === RegularCycle::MONDAY) {
            if ($weekNumber !== RegularCycle::MONDAY) {
                $DateTime->modify('next Monday');
            }

            return $DateTime->modify('next Monday');
        } elseif ($RegularCycle->getWeek() === RegularCycle::TUESDAY) {
            if ($weekNumber !== RegularCycle::TUESDAY) {
                $DateTime->modify('next Tuesday');
            }

            return $DateTime->modify('next Tuesday');
        } elseif ($RegularCycle->getWeek() === RegularCycle::WEDNESDAY) {
            if ($weekNumber !== RegularCycle::WEDNESDAY) {
                $DateTime->modify('next Wednesday');
            }

            return $DateTime->modify('next Wednesday');
        } elseif ($RegularCycle->getWeek() === RegularCycle::THURSDAY) {
            if ($weekNumber !== RegularCycle::THURSDAY) {
                $DateTime->modify('next Thursday');
            }

            return $DateTime->modify('next Thursday');
        } elseif ($RegularCycle->getWeek() === RegularCycle::FRIDAY) {
            if ($weekNumber !== RegularCycle::FRIDAY) {
                $DateTime->modify('next Friday');
            }

            return $DateTime->modify('next Friday');
        } elseif ($RegularCycle->getWeek() === RegularCycle::SATURDAY) {
            if ($weekNumber !== RegularCycle::SATURDAY) {
                $DateTime->modify('next Saturday');
            }

            return $DateTime->modify('next Saturday');
        }

        return $DateTime->modify('+1 week');
    }
}
