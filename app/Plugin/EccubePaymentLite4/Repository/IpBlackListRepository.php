<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\IpBlackList;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IpBlackListRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, IpBlackList::class);
    }
}
