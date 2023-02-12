<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;

class ChangeRegularStatusToRePaymentService
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    public function __construct(
        RegularOrderRepository $regularOrderRepository,
        RegularStatusRepository $regularStatusRepository,
        EntityManagerInterface $entityManager,
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
        $this->regularStatusRepository = $regularStatusRepository;
        $this->entityManager = $entityManager;
        $this->isActiveRegularService = $isActiveRegularService;
    }

    public function handle(Customer $Customer)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return;
        }
        /** @var RegularStatus $RegularStatusPaymentError */
        $RegularStatusPaymentError = $this->regularStatusRepository->find(RegularStatus::PAYMENT_ERROR);
        /** @var RegularOrder[] $RegularOrders */
        $RegularOrders = $this->regularOrderRepository->findBy([
            'Customer' => $Customer,
            'RegularStatus' => $RegularStatusPaymentError,
        ]);
        if (empty($RegularOrders)) {
            return;
        }
        /** @var RegularStatus $RegularStatus */
        $RegularStatus = $this->regularStatusRepository->find(RegularStatus::WAITING_RE_PAYMENT);
        foreach ($RegularOrders as $RegularOrder) {
            $RegularOrder->setRegularStatus($RegularStatus);
            $this->entityManager->persist($RegularOrder);
        }
        $this->entityManager->flush();
    }
}
