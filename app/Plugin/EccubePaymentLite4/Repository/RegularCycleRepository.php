<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularCycleRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularCycle::class);
    }
}
