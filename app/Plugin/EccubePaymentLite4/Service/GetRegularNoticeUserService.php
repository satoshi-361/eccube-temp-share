<?php

namespace Plugin\EccubePaymentLite4\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class GetRegularNoticeUserService
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

    public function handle()
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $regularDeliveryNotificationEmailDate = $Config->getRegularDeliveryNotificationEmailDays();
        if (is_null($regularDeliveryNotificationEmailDate)) {
            return [];
        }
        // 次回配送予定日の5日前(定期配送お知らせメール日で設定されている数値の定期受注を取得
        $today = new DateTime('today');
        $sendMailDateStart = $today->modify('+'.$regularDeliveryNotificationEmailDate.' day');
        $today = new DateTime('today');
        $sendMailDateEnd = $today->modify('+'.($regularDeliveryNotificationEmailDate + 1).' day');
        $qb = $this
            ->entityManager
            ->createQueryBuilder()
            ->select('rs')
            ->from(RegularShipping::class, 'rs')
            ->where('rs.next_delivery_date >= :next_delivery_date_start')
            ->setParameter('next_delivery_date_start', $sendMailDateStart)
            ->andWhere('rs.next_delivery_date < :next_delivery_date_end')
            ->setParameter('next_delivery_date_end', $sendMailDateEnd);

        return $qb
            ->getQuery()
            ->getArrayResult();
    }
}
