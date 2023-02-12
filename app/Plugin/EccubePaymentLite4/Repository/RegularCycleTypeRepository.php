<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularCycleTypeRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularCycleType::class);
    }
}
