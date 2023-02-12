<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class GetCustomerForSendChangeCardMailService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigRepository $configRepository
    ) {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
    }

    public function get()
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $lastDayOfThisMonth = new \DateTime('last day of this month 00:00:00');
        $expirationDate = $lastDayOfThisMonth->modify('- '.$Config->getCardExpirationNotificationDays().'day');
        $now = new \DateTime();
        $qb = $this
            ->entityManager
            ->createQueryBuilder()
            ->select('c.id')
            ->from(RegularOrder::class, 'ro')
            ->innerJoin('ro.Customer', 'c');
        $qb
            ->where($qb->expr()->isNull('c.card_change_request_mail_send_date'))
            ->andWhere(':now > :expirationDate')
            ->setParameter('now', $now)
            ->setParameter('expirationDate', $expirationDate)
            ->andWhere('ro.RegularStatus = :RegularStatus')
            ->setParameter('RegularStatus', RegularStatus::CONTINUE)
            ->groupBy('c.id');

        return $qb
            ->getQuery()
            ->getResult();
    }
}
