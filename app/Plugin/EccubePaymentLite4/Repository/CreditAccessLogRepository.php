<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\CreditAccessLog;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CreditAccessLogRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CreditAccessLog::class);
    }

    public function get($id = 1)
    {
        return $this->find($id);
    }

    public function deleteAllIpAddressForPassedAccessFrequencyTime($access_frequency_time)
    {
        $date = new \DateTime();
        $date->modify("-$access_frequency_time seconds");

        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.access_date < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
