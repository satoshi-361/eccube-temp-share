<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\CreditBlock;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CreditBlockRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CreditBlock::class);
    }

    public function get($id = 1)
    {
        return $this->find($id);
    }

    public function deleteAllIpAddressForPassedBlockTime($block_time)
    {
        $date = new \DateTime();
        $date->modify("-$block_time seconds");

        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.block_date < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
