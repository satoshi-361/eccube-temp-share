<?php

namespace Plugin\EccubePaymentLite4\Service;

use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class NextDeliveryDateService
{
    /**
     * @var CalculateNextDeliveryDateService
     */
    private $calculateNextDeliveryDateService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        CalculateNextDeliveryDateService $calculateNextDeliveryDateService,
        ConfigRepository $configRepository
    ) {
        $this->calculateNextDeliveryDateService = $calculateNextDeliveryDateService;
        $this->configRepository = $configRepository;
    }

    public function getStartDateTime(string $day)
    {
        $nextDeliveryDate = new \DateTime('today');
        $nextDeliveryDate->modify('+'.$day.'day');

        return $nextDeliveryDate;
    }

    public function getEndDateTime(string $day, RegularOrder $RegularOrder)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);

        return $this
            ->calculateNextDeliveryDateService
            ->calc(
                $RegularOrder->getRegularCycle(),
                $Config->getRegularOrderDeadline()
            );
    }
}
