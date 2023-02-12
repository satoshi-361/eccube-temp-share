<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;

class UpdateRegularStatusService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RegularStatusRepository $regularStatusRepository
    ) {
        $this->entityManager = $entityManager;
        $this->regularStatusRepository = $regularStatusRepository;
    }

    public function handle(RegularOrder $RegularOrder, $regularStatusId): RegularStatus
    {
        /** @var RegularStatus $RegularStatus */
        $RegularStatus = $this->regularStatusRepository->find($regularStatusId);
        $RegularOrder->setRegularStatus($RegularStatus);
        $this->entityManager->persist($RegularOrder);
        $this->entityManager->flush();

        return $RegularOrder->getRegularStatus();
    }
}
